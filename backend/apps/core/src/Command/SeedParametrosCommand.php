<?php

namespace App\apps\core\Command;

use App\apps\core\Entity\Parametro;
use App\apps\core\Repository\ParametroRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-parametros',
    description: 'Siembra los parámetros base del sistema (TIPOSERVICIO, MONEDA, UNIDMEDIDA). Seguro de re-ejecutar, omite los que ya existen.'
)]
class SeedParametrosCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParametroRepository $parametroRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seed de parámetros base');

        $grupos = [
            [
                'name'  => 'Tipo de Servicio',
                'alias' => 'TIPOSERVICIO',
                'hijos' => [
                    ['name' => 'MAQUILA',     'alias' => 'MAQUILA'],
                    ['name' => 'SOBRECOSTO',  'alias' => 'SOBRECOSTO'],
                    ['name' => 'VENTA_CAJAS', 'alias' => 'VENTA_CAJAS'],
                ],
            ],
            [
                'name'  => 'Moneda',
                'alias' => 'MONEDA',
                'hijos' => [
                    ['name' => 'USD', 'alias' => 'USD'],
                    ['name' => 'PEN', 'alias' => 'PEN'],
                ],
            ],
            [
                'name'  => 'Unidad de Medida',
                'alias' => 'UNIDMEDIDA',
                'hijos' => [
                    ['name' => 'TNE', 'alias' => 'TNE'],
                    ['name' => 'KGM', 'alias' => 'KGM'],
                    ['name' => 'KG',  'alias' => 'KG'],
                    ['name' => 'ZZ',  'alias' => 'ZZ'],
                    ['name' => 'UND', 'alias' => 'UND'],
                    ['name' => 'NIU', 'alias' => 'NIU'],
                ],
            ],
        ];

        $creados = 0;
        $omitidos = 0;

        foreach ($grupos as $grupo) {
            $io->section(sprintf('Grupo: %s (%s)', $grupo['name'], $grupo['alias']));

            // Buscar o crear el padre
            $padre = $this->parametroRepository->findByAlias($grupo['alias']);
            if ($padre === null) {
                $padre = new Parametro();
                $padre->setName($grupo['name']);
                $padre->setAlias($grupo['alias']);
                $this->em->persist($padre);
                $this->em->flush();
                $io->writeln(sprintf('  ✓ Padre creado: %s', $grupo['alias']));
                $creados++;
            } else {
                $io->writeln(sprintf('  · Padre ya existe: %s (omitido)', $grupo['alias']));
                $omitidos++;
            }

            // Buscar o crear cada hijo
            foreach ($grupo['hijos'] as $hijoDef) {
                $hijoExistente = $this->parametroRepository->findByAlias($hijoDef['alias']);
                if ($hijoExistente === null) {
                    $hijo = new Parametro();
                    $hijo->setName($hijoDef['name']);
                    $hijo->setAlias($hijoDef['alias']);
                    $hijo->setParent($padre);
                    $this->em->persist($hijo);
                    $this->em->flush();
                    $io->writeln(sprintf('    ✓ Hijo creado: %s → %s', $hijoDef['alias'], $grupo['alias']));
                    $creados++;
                } else {
                    $io->writeln(sprintf('    · Hijo ya existe: %s (omitido)', $hijoDef['alias']));
                    $omitidos++;
                }
            }
        }

        $io->success(sprintf('Completado. Creados: %d | Omitidos: %d', $creados, $omitidos));

        return Command::SUCCESS;
    }
}
