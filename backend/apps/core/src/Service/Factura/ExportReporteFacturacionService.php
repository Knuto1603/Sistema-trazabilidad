<?php

namespace App\apps\core\Service\Factura;

use App\apps\core\Repository\FacturaRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ExportReporteFacturacionService
{
    public function __construct(
        private FacturaRepository $facturaRepository,
    ) {
    }

    public function execute(?string $search = null): Response
    {
        $facturas = $this->facturaRepository->findAllForReporte($search);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Facturacion');

        // === TÍTULO ===
        $sheet->mergeCells('A1:V1');
        $sheet->setCellValue('A1', 'REPORTE DE FACTURACION GENERAL-INTERFRUITS');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        // === FILA SUMA ===
        $lastRow = count($facturas) + 3;
        $sheet->setCellValue('O2', "=SUM(O4:O{$lastRow})");
        $sheet->getStyle('O2')->applyFromArray([
            'font' => ['bold' => true],
            'numberFormat' => ['formatCode' => '#,##0.00'],
        ]);

        // === CABECERAS ===
        $headers = [
            'A' => 'SEM',
            'B' => 'MES',
            'C' => 'RUC',
            'D' => 'CLIENTE',
            'E' => 'FECHA',
            'F' => 'N° GUIA',
            'G' => 'N°FACTURA',
            'H' => 'KG',
            'I' => 'DETALLE',
            'J' => 'SERVICIO',
            'K' => 'U.M',
            'L' => 'CAJAS',
            'M' => 'CANTIDAD',
            'N' => 'V.U',
            'O' => 'IMPORTE',
            'P' => 'IGV',
            'Q' => 'TOTAL',
            'R' => 'T.C',
            'S' => 'PRODUCTO',
            'T' => 'SEDE',
            'U' => 'DESTINO',
            'V' => 'TIPO DE OPERACIÓN',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}3", $label);
        }

        $headerRange = 'A3:V3';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(18);

        // === DATOS ===
        $row = 4;
        foreach ($facturas as $factura) {
            $despacho = $factura->getDespacho();
            $cliente = $despacho?->getCliente();
            $fruta = $despacho?->getFruta();

            $fecha = $factura->getFechaEmision();
            $fechaStr = $fecha?->format('Y-m-d');

            // Número de semana ISO
            $semana = $fecha ? (int) $fecha->format('W') : null;
            $mes = $fecha ? strtoupper((new \IntlDateFormatter(
                'es_ES',
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                null,
                null,
                'MMMM'
            ))->format($fecha)) : null;

            // Fallback si IntlDateFormatter no está disponible
            if ($mes === null && $fecha !== null) {
                $meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
                $mes = $meses[(int)$fecha->format('n') - 1];
            }

            $anulada = $factura->isAnulada();

            $values = [
                'A' => $semana,
                'B' => $mes,
                'C' => $cliente?->getRuc(),
                'D' => $cliente?->getRazonSocial(),
                'E' => $fechaStr ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($fecha) : null,
                'F' => $factura->getNumeroGuia(),
                'G' => $factura->getNumeroDocumento(),
                'H' => $anulada ? 0 : $factura->getKgCaja(),
                'I' => $anulada ? 'ANULADA' : $factura->getDetalle(),
                'J' => $factura->getTipoServicio(),
                'K' => $factura->getUnidadMedida(),
                'L' => $anulada ? 0 : $factura->getCajas(),
                'M' => $anulada ? 0 : ($factura->getCantidad() !== null ? (float) $factura->getCantidad() : null),
                'N' => $anulada ? 0 : ($factura->getValorUnitario() !== null ? (float) $factura->getValorUnitario() : null),
                'O' => $anulada ? 0 : ($factura->getImporte() !== null ? (float) $factura->getImporte() : null),
                'P' => $anulada ? 0 : ($factura->getIgv() !== null ? (float) $factura->getIgv() : null),
                'Q' => $anulada ? 0 : ($factura->getTotal() !== null ? (float) $factura->getTotal() : null),
                'R' => $factura->getTipoCambio() !== null ? (float) $factura->getTipoCambio() : null,
                'S' => $fruta?->getNombre(),
                'T' => $despacho?->getSede(),
                'U' => $factura->getDestino(),
                'V' => $factura->getTipoOperacion(),
            ];

            foreach ($values as $col => $value) {
                $cell = $sheet->getCell("{$col}{$row}");
                $cell->setValue($value);
            }

            // Formato fecha en columna E
            if ($fechaStr) {
                $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('DD/MM/YYYY');
            }

            // Formato numérico
            $sheet->getStyle("O{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("P{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("Q{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("M{$row}")->getNumberFormat()->setFormatCode('#,##0.0000');
            $sheet->getStyle("N{$row}")->getNumberFormat()->setFormatCode('#,##0.0000');
            $sheet->getStyle("R{$row}")->getNumberFormat()->setFormatCode('#,##0.000');

            // Color de fila alternada
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:V{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
            }

            // Marcar anuladas con fondo rojizo (sin tachado)
            if ($factura->isAnulada()) {
                $sheet->getStyle("A{$row}:V{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => '9CA3AF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                ]);
            }

            $row++;
        }

        // Bordes en área de datos
        if ($row > 4) {
            $sheet->getStyle("A3:V" . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
        }

        // === ANCHOS DE COLUMNA ===
        $widths = [
            'A' => 6,  'B' => 12, 'C' => 13, 'D' => 35,
            'E' => 12, 'F' => 14, 'G' => 14, 'H' => 6,
            'I' => 60, 'J' => 12, 'K' => 6,  'L' => 8,
            'M' => 10, 'N' => 10, 'O' => 12, 'P' => 12,
            'Q' => 12, 'R' => 8,  'S' => 10, 'T' => 12,
            'U' => 14, 'V' => 16,
        ];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Ajuste de texto en columna DETALLE
        $sheet->getStyle('I4:I' . ($row - 1))->getAlignment()->setWrapText(true);

        // Freeze panes
        $sheet->freezePane('A4');

        // === RESPUESTA STREAMED ===
        $writer = new Xlsx($spreadsheet);
        $filename = 'Reporte_Facturacion_' . date('Y-m-d_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');

        return $response;
    }
}
