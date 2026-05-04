<?php

namespace App\apps\core\Command;

use App\apps\core\Repository\ArchivoDespachoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:vincular-pdfs',
    description: 'Vincula PDFs de factura/guía existentes a su factura correspondiente por coincidencia de nombre con el XML.'
)]
class VincularPdfFacturaCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArchivoDespachoRepository $archivoDespachoRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Solo muestra qué se vincularía sin guardar cambios');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Modo DRY-RUN: no se guardarán cambios.');
        }

        $pdfs = $this->em->createQuery(
            'SELECT a FROM App\apps\core\Entity\ArchivoDespacho a
             WHERE a.tipoArchivo IN (:tipos)
             AND a.factura IS NULL
             AND a.isActive = true'
        )
        ->setParameter('tipos', ['FACTURA_PDF', 'GUIA_PDF'])
        ->getResult();

        $io->title(sprintf('PDFs sin vincular encontrados: %d', count($pdfs)));

        if (count($pdfs) === 0) {
            $io->success('Todos los PDFs ya están vinculados a una factura.');
            return Command::SUCCESS;
        }

        $vinculados  = 0;
        $sinMatch    = 0;

        foreach ($pdfs as $pdf) {
            $originalName = $this->extractOriginalName($pdf->getNombre());
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $despacho = $pdf->getDespacho();

            $matching = $this->archivoDespachoRepository
                ->findXmlWithFacturaByDespachoAndBaseName($despacho, $baseName);

            if ($matching === null) {
                $io->writeln(sprintf('<comment>[SIN MATCH] %s</comment>', $pdf->getNombre()));
                $sinMatch++;
                continue;
            }

            $factura = $matching->getFactura();
            $io->writeln(sprintf(
                '<info>[OK] %s → %s</info>',
                $pdf->getNombre(),
                $factura->getNumeroDocumento()
            ));

            if (!$dryRun) {
                $pdf->setFactura($factura);
                $this->em->persist($pdf);
            }

            $vinculados++;
        }

        if (!$dryRun && $vinculados > 0) {
            $this->em->flush();
        }

        $io->newLine();
        $io->table(
            ['Resultado', 'Cantidad'],
            [
                ['Vinculados', $vinculados],
                ['Sin match por nombre', $sinMatch],
            ]
        );

        if ($dryRun) {
            $io->note('DRY-RUN completado. Ejecuta sin --dry-run para aplicar los cambios.');
        } else {
            $io->success(sprintf('Proceso completado. %d PDFs vinculados.', $vinculados));
        }

        return Command::SUCCESS;
    }

    private function extractOriginalName(string $storedName): string
    {
        // El nombre guardado tiene formato: {uniqid}_{nombreOriginal}
        $pos = strpos($storedName, '_');
        return $pos !== false ? substr($storedName, $pos + 1) : $storedName;
    }
}
