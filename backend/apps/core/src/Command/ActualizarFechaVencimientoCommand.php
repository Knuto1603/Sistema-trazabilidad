<?php

namespace App\apps\core\Command;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Service\Xml\XmlDocumentoParserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:actualizar-fecha-vencimiento',
    description: 'Re-parsea los XMLs subidos y actualiza fechaVencimiento en facturas que aún no la tienen.'
)]
class ActualizarFechaVencimientoCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private FacturaRepository $facturaRepository,
        private ArchivoDespachoRepository $archivoDespachoRepository,
        private XmlDocumentoParserService $parser,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Solo muestra qué se actualizaría sin guardar cambios');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Modo DRY-RUN: no se guardarán cambios.');
        }

        // Facturas activas sin fechaVencimiento
        $facturas = $this->em->createQuery(
            'SELECT f FROM App\apps\core\Entity\Factura f
             WHERE f.isActive = true
             AND f.fechaVencimiento IS NULL
             AND f.tipoDocumento IN (:tipos)'
        )
        ->setParameter('tipos', ['01', '08']) // facturas y notas de débito tienen DueDate
        ->getResult();

        $io->title('Facturas sin fecha de vencimiento: ' . count($facturas));

        if (count($facturas) === 0) {
            $io->success('Todas las facturas ya tienen fecha de vencimiento.');
            return Command::SUCCESS;
        }

        $tiposXml = ['FACTURA_XML', 'NOTA_CREDITO_XML', 'NOTA_DEBITO_XML'];
        $actualizadas = 0;
        $sinArchivo   = 0;
        $sinFecha     = 0;
        $errores      = 0;

        foreach ($facturas as $factura) {
            $numeroDoc = $factura->getNumeroDocumento();

            // Buscar archivo XML vinculado directamente a esta factura
            $archivos = $this->em->createQuery(
                'SELECT a FROM App\apps\core\Entity\ArchivoDespacho a
                 WHERE a.factura = :factura
                 AND a.tipoArchivo IN (:tipos)
                 AND a.isActive = true'
            )
            ->setParameter('factura', $factura)
            ->setParameter('tipos', $tiposXml)
            ->getResult();

            // Si no hay vínculo directo, buscar en el mismo despacho por nombre de archivo
            if (empty($archivos) && $factura->getDespacho()) {
                $archivos = $this->em->createQuery(
                    'SELECT a FROM App\apps\core\Entity\ArchivoDespacho a
                     WHERE a.despacho = :despacho
                     AND a.tipoArchivo IN (:tipos)
                     AND a.isActive = true'
                )
                ->setParameter('despacho', $factura->getDespacho())
                ->setParameter('tipos', $tiposXml)
                ->getResult();

                // Filtrar por número de documento en el nombre del archivo
                $serie = $factura->getSerie();
                $correlativo = $factura->getCorrelativo();
                $archivos = array_filter($archivos, function ($a) use ($serie, $correlativo) {
                    $nombre = strtolower($a->getNombre());
                    $s = strtolower($serie ?? '');
                    $c = ltrim((string)($correlativo ?? ''), '0');
                    return str_contains($nombre, strtolower($s)) && str_contains($nombre, $c);
                });
            }

            if (empty($archivos)) {
                $io->writeln("<comment>[SIN ARCHIVO] {$numeroDoc}</comment>");
                $sinArchivo++;
                continue;
            }

            // Usar el primer archivo encontrado
            $archivo = array_values($archivos)[0];
            $rutaAbsoluta = $this->projectDir . '/public/' . $archivo->getRuta();

            if (!file_exists($rutaAbsoluta)) {
                $io->writeln("<error>[ARCHIVO NO EXISTE] {$numeroDoc} → {$archivo->getRuta()}</error>");
                $errores++;
                continue;
            }

            try {
                $contenido = file_get_contents($rutaAbsoluta);
                $data = $this->parser->parse($contenido);
                $fechaVencimiento = $data['fechaVencimiento'] ?? null;

                if (!$fechaVencimiento) {
                    $io->writeln("<comment>[SIN DueDate en XML] {$numeroDoc}</comment>");
                    $sinFecha++;
                    continue;
                }

                if (!$dryRun) {
                    $factura->setFechaVencimiento(new \DateTime($fechaVencimiento));
                    $this->em->persist($factura);
                }

                $io->writeln("<info>[OK] {$numeroDoc} → {$fechaVencimiento}</info>");
                $actualizadas++;

            } catch (\Throwable $e) {
                $io->writeln("<error>[ERROR] {$numeroDoc}: {$e->getMessage()}</error>");
                $errores++;
            }
        }

        if (!$dryRun && $actualizadas > 0) {
            $this->em->flush();
        }

        $io->newLine();
        $io->table(
            ['Resultado', 'Cantidad'],
            [
                ['Actualizadas', $actualizadas],
                ['Sin archivo XML vinculado', $sinArchivo],
                ['XML sin DueDate', $sinFecha],
                ['Errores', $errores],
            ]
        );

        if ($dryRun) {
            $io->note('DRY-RUN completado. Ejecuta sin --dry-run para aplicar los cambios.');
        } else {
            $io->success("Proceso completado. {$actualizadas} facturas actualizadas.");
        }

        return Command::SUCCESS;
    }
}
