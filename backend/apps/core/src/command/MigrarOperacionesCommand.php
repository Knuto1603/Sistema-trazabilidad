<?php

namespace App\apps\core\Command;

use App\apps\core\Entity\Operacion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrar-operaciones',
    description: 'Crea las operaciones por defecto y asigna los despachos existentes. Ejecutar UNA sola vez.'
)]
class MigrarOperacionesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->em->getConnection();

        // Verificar si ya se ejecutó
        $existentes = $conn->fetchOne('SELECT COUNT(*) FROM core_operacion');
        if ((int) $existentes > 0) {
            if (!$io->confirm(
                sprintf('Ya existen %d operaciones en la BD. ¿Desea continuar de todas formas?', $existentes),
                false
            )) {
                $io->warning('Operación cancelada.');
                return Command::SUCCESS;
            }
        }

        $io->title('Migración de operaciones');

        $conn->beginTransaction();
        try {
            // PASO B: Crear operaciones por defecto
            $io->section('Paso 1: Crear operaciones por defecto');

            $operacionesDef = [
                ['nombre' => 'MARITIMO',        'sede' => 'SULLANA'],
                ['nombre' => 'MARITIMO EUROPA', 'sede' => 'TAMBOGRANDE'],
                ['nombre' => 'MARITIMO USA',    'sede' => 'TAMBOGRANDE'],
                ['nombre' => 'AEREO',           'sede' => 'TAMBOGRANDE'],
                ['nombre' => 'AEREO USA',       'sede' => 'TAMBOGRANDE'],
            ];

            $operacionIds = [];
            foreach ($operacionesDef as $def) {
                $op = new Operacion();
                $op->setNombre($def['nombre']);
                $op->setSede($def['sede']);
                $this->em->persist($op);
                $this->em->flush();
                $operacionIds[$def['nombre']] = $op->getId();
                $io->writeln(sprintf('  ✓ Creada: %s (%s) → id=%d', $def['nombre'], $def['sede'], $op->getId()));
            }

            // PASO C: Asignar "MARITIMO" a despachos de SULLANA
            $io->section('Paso 2: Asignar operación MARITIMO a despachos de SULLANA');
            $maritimoId = $operacionIds['MARITIMO'];
            $afectados = $conn->executeStatement(
                'UPDATE core_despacho SET operacion_id = :opId WHERE sede = :sede',
                ['opId' => $maritimoId, 'sede' => 'SULLANA']
            );
            $io->success(sprintf('%d despachos de SULLANA asignados a MARITIMO', $afectados));

            // PASO D: Contar TAMBOGRANDE pendientes
            $io->section('Paso 3: Despachos TAMBOGRANDE');
            $pendientes = $conn->fetchOne(
                'SELECT COUNT(*) FROM core_despacho WHERE sede = :sede',
                ['sede' => 'TAMBOGRANDE']
            );
            $io->warning(sprintf(
                '%d despachos de TAMBOGRANDE quedan sin operación asignada. Asignar manualmente desde el panel de administración.',
                $pendientes
            ));

            // PASO E: Inferir sede de campañas desde fruta ↔ despacho
            $io->section('Paso 4: Inferir sede de campañas');
            $campahnas = $conn->fetchAllAssociative(
                'SELECT c.id, c.fruta_id FROM core_campahna c WHERE c.sede IS NULL'
            );

            $sedesInferidas = 0;
            $sinSede = 0;
            foreach ($campahnas as $camp) {
                $frutaId = $camp['fruta_id'];
                // Ver si todos los despachos de esa fruta son de la misma sede
                $sedes = $conn->fetchAllAssociative(
                    'SELECT DISTINCT sede FROM core_despacho WHERE fruta_id = :frutaId AND sede IS NOT NULL',
                    ['frutaId' => $frutaId]
                );

                if (count($sedes) === 1) {
                    $sede = $sedes[0]['sede'];
                    $conn->executeStatement(
                        'UPDATE core_campahna SET sede = :sede WHERE id = :id',
                        ['sede' => $sede, 'id' => $camp['id']]
                    );
                    $sedesInferidas++;
                } else {
                    $sinSede++;
                }
            }
            $io->writeln(sprintf('  Campañas con sede inferida: %d', $sedesInferidas));
            if ($sinSede > 0) {
                $io->warning(sprintf('%d campaña(s) no pudieron inferir sede (revisar manualmente)', $sinSede));
            }

            $conn->commit();

            // REPORTE FINAL
            $io->success('¡Migración completada exitosamente!');
            $io->table(
                ['Concepto', 'Cantidad'],
                [
                    ['Operaciones creadas', count($operacionesDef)],
                    ['Despachos SULLANA asignados', $afectados],
                    ['Despachos TAMBOGRANDE pendientes', $pendientes],
                    ['Campañas con sede inferida', $sedesInferidas],
                    ['Campañas sin sede (revisar)', $sinSede],
                ]
            );

        } catch (\Throwable $e) {
            $conn->rollBack();
            $io->error('Rollback ejecutado. Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
