<?php

namespace App\apps\core\Service\Xml;

class XmlDocumentoParserService
{
    public function parse(string $xmlContent): array
    {
        $xml = new \SimpleXMLElement($xmlContent);
        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $rootName = $xml->getName();

        if ($rootName === 'Invoice') {
            return $this->parseInvoice($xml);
        }

        if ($rootName === 'DespatchAdvice') {
            return $this->parseDespatchAdvice($xml);
        }

        if ($rootName === 'DebitNote') {
            return $this->parseDebitNote($xml);
        }

        // CDR de SUNAT: confirmación de recepción, no contiene datos de factura
        if ($rootName === 'ApplicationResponse') {
            return ['tipoDocumento' => 'CDR', 'ignorar' => true];
        }

        throw new \RuntimeException('Tipo de documento XML no soportado: ' . $rootName);
    }

    private function parseInvoice(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $tipoDocumento = $this->xpathValue($xml, '//cbc:InvoiceTypeCode');
        $id = $this->xpathValue($xml, '//cbc:ID');
        $parts = explode('-', $id ?? '', 2);
        $serie = $parts[0] ?? '';
        $correlativo = $parts[1] ?? '';

        $fechaEmision = $this->xpathValue($xml, '//cbc:IssueDate');
        $fechaVencimiento = $this->xpathValue($xml, '//cbc:DueDate');
        $moneda = $this->xpathValue($xml, '//cbc:DocumentCurrencyCode');

        $rucCliente = null;
        $partyIds = $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID');
        foreach ($partyIds as $pid) {
            $attrs = $pid->attributes();
            if (isset($attrs['schemeID']) && (string) $attrs['schemeID'] === '6') {
                $rucCliente = (string) $pid;
                break;
            }
        }

        $nombreCliente = $this->xpathValue($xml, '//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName');
        if (!$nombreCliente) {
            $nombreCliente = $this->xpathValue($xml, '//cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name');
        }

        $numeroGuiaRaw = $this->xpathValue($xml, '//cac:DespatchDocumentReference/cbc:ID');
        $numeroGuia = $numeroGuiaRaw ? $this->normalizarNumeroGuia($numeroGuiaRaw) : null;

        // Extraer todas las líneas del comprobante
        $invoiceLines = $xml->xpath('//cac:InvoiceLine');
        $items = [];

        foreach ($invoiceLines as $line) {
            $line->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $line->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

            // Cantidad y unidad por línea
            $lineCantidad = null;
            $lineUnidad = null;
            $qtyNodes = $line->xpath('cbc:InvoicedQuantity');
            if (!empty($qtyNodes)) {
                $lineCantidad = (float)(string)$qtyNodes[0];
                $attrs = $qtyNodes[0]->attributes();
                $lineUnidad = isset($attrs['unitCode']) ? (string)$attrs['unitCode'] : null;
            }

            // Importe por línea (valor venta sin IGV)
            $lineImporte = null;
            $importeNodes = $line->xpath('cbc:LineExtensionAmount');
            if (!empty($importeNodes)) {
                $lineImporte = (float)(string)$importeNodes[0] ?: null;
            }

            // IGV por línea
            $lineIgv = null;
            $igvNodes = $line->xpath('cac:TaxTotal/cbc:TaxAmount');
            if (!empty($igvNodes)) {
                $lineIgv = (float)(string)$igvNodes[0];
                if ($lineIgv === 0.0) $lineIgv = 0.0; // mantener 0 explícito
            }

            // Total por línea
            $lineTotal = null;
            if ($lineImporte !== null) {
                $lineTotal = $lineImporte + ($lineIgv ?? 0.0);
            }

            // Valor unitario por línea
            $lineVU = null;
            $vuNodes = $line->xpath('cac:Price/cbc:PriceAmount');
            if (!empty($vuNodes)) {
                $lineVU = (float)(string)$vuNodes[0] ?: null;
            }

            // Descripción por línea
            $lineDetalle = null;
            $descNodes = $line->xpath('cac:Item/cbc:Description');
            if (!empty($descNodes)) {
                $lineDetalle = trim((string)$descNodes[0]) ?: null;
            }

            // Extraer kgCaja, cajas, tipoOperacion, contenedor, tipoServicio del detalle
            $lineKgCaja = null;
            if ($lineDetalle && preg_match('/EN CAJAS DE (\d+)\s*KG/i', $lineDetalle, $m)) {
                $lineKgCaja = (int)$m[1];
            }

            $lineCajas = null;
            if ($lineKgCaja !== null && $lineCantidad !== null && $lineUnidad === 'TNE') {
                $lineCajas = (int)round($lineCantidad * 1000 / $lineKgCaja);
            } elseif ($lineKgCaja !== null && $lineCantidad !== null && $lineUnidad === 'KGM') {
                $lineCajas = (int)round($lineCantidad / $lineKgCaja);
            }

            $lineTipoOperacion = null;
            if ($lineDetalle) {
                if (preg_match('/MAR[IÍ]TIMA|MARITIM[AO]/i', $lineDetalle)) {
                    $lineTipoOperacion = 'MARITIMO';
                } elseif (stripos($lineDetalle, 'TERRESTRE') !== false) {
                    $lineTipoOperacion = 'TERRESTRE';
                }
            }

            $lineContenedor = null;
            if ($lineDetalle && preg_match('/CONTENEDOR\s+N[°o°]?:?\s*([A-Z0-9\s\-]+)/i', $lineDetalle, $m)) {
                $lineContenedor = trim($m[1]);
            }

            $lineTipoServicio = null;
            if ($lineDetalle) {
                if (stripos($lineDetalle, 'MAQUILA') !== false) {
                    $lineTipoServicio = 'MAQUILA';
                } elseif (stripos($lineDetalle, 'SOBRECOSTO') !== false || stripos($lineDetalle, 'COMPLEMENTARIO') !== false) {
                    $lineTipoServicio = 'SOBRECOSTO';
                } else {
                    $lineTipoServicio = 'VENTA_CAJAS';
                }
            }

            $items[] = [
                'cantidad'       => $lineCantidad,
                'unidadMedida'   => $lineUnidad,
                'valorUnitario'  => $lineVU,
                'importe'        => $lineImporte,
                'igv'            => $lineIgv,
                'total'          => $lineTotal,
                'kgCaja'         => $lineKgCaja,
                'cajas'          => $lineCajas,
                'detalle'        => $lineDetalle,
                'tipoOperacion'  => $lineTipoOperacion,
                'contenedor'     => $lineContenedor,
                'tipoServicio'   => $lineTipoServicio,
            ];
        }

        // Si no hay líneas (raro), fallback al comportamiento anterior
        if (empty($items)) {
            $items[] = [
                'cantidad'      => null,
                'unidadMedida'  => null,
                'valorUnitario' => null,
                'importe'       => (float)$this->xpathValue($xml, '//cac:LegalMonetaryTotal/cbc:LineExtensionAmount') ?: null,
                'igv'           => (float)$this->xpathValue($xml, '//cac:TaxTotal/cbc:TaxAmount') ?: null,
                'total'         => (float)$this->xpathValue($xml, '//cac:LegalMonetaryTotal/cbc:PayableAmount') ?: null,
                'kgCaja'        => null,
                'cajas'         => null,
                'detalle'       => null,
                'tipoOperacion' => null,
                'contenedor'    => null,
                'tipoServicio'  => null,
            ];
        }

        $first = $items[0];

        return [
            'tipoDocumento'   => $tipoDocumento ?? '01',
            'serie'           => $serie,
            'correlativo'     => $correlativo,
            'numeroDocumento' => $id,
            'numeroGuia'      => $numeroGuia,
            'fechaEmision'    => $fechaEmision,
            'fechaVencimiento'=> $fechaVencimiento,
            'moneda'          => $moneda ?? 'USD',
            'rucCliente'     => $rucCliente,
            'nombreCliente'  => $nombreCliente,
            'items'          => $items,
            // Compatibilidad hacia atrás: campos del primer ítem en el nivel raíz
            'detalle'        => $first['detalle'],
            'cantidad'       => $first['cantidad'],
            'unidadMedida'   => $first['unidadMedida'],
            'cajas'          => $first['cajas'],
            'valorUnitario'  => $first['valorUnitario'],
            'importe'        => $first['importe'],
            'igv'            => $first['igv'],
            'total'          => $first['total'],
            'kgCaja'         => $first['kgCaja'],
            'tipoOperacion'  => $first['tipoOperacion'],
            'contenedor'     => $first['contenedor'],
            'tipoServicio'   => $first['tipoServicio'],
        ];
    }

    private function parseDespatchAdvice(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $id = $this->xpathValue($xml, '//cbc:ID');
        $parts = explode('-', $id ?? '', 2);
        $serie = $parts[0] ?? '';
        $correlativo = $parts[1] ?? '';

        $fechaEmision = $this->xpathValue($xml, '//cbc:IssueDate');

        $rucCliente = null;
        $partyIds = $xml->xpath('//cac:DeliveryCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID');
        foreach ($partyIds as $pid) {
            $attrs = $pid->attributes();
            if (isset($attrs['schemeID']) && (string) $attrs['schemeID'] === '6') {
                $rucCliente = (string) $pid;
                break;
            }
        }
        if (!$rucCliente && !empty($partyIds)) {
            $rucCliente = (string) $partyIds[0];
        }

        $nombreCliente = $this->xpathValue($xml, '//cac:DeliveryCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName');

        // Extraer líneas de despacho: puede haber bultos (cajas) y peso
        $cajas = null;
        $cantidad = null;
        $unidadMedida = null;

        $despatchLines = $xml->xpath('//cac:DespatchLine');
        foreach ($despatchLines as $line) {
            $line->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $line->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $quantities = $line->xpath('cbc:DeliveredQuantity');
            foreach ($quantities as $q) {
                $attrs = $q->attributes();
                $unit = isset($attrs['unitCode']) ? strtoupper((string) $attrs['unitCode']) : '';
                $val = (float) (string) $q;
                // Bultos/cajas: unitCode BX (box), NIU (número de unidades), ZZ, CAJA, etc.
                if (in_array($unit, ['BX', 'NIU', 'ZZ', 'CAJA', 'C62', 'PACK', 'CT'], true)) {
                    $cajas = (int) round($val);
                } elseif (in_array($unit, ['TNE', 'KGM', 'GRM'], true)) {
                    $cantidad = $val;
                    $unidadMedida = $unit;
                }
            }
        }

        // Fallback: DeliveredQuantity en TNE si no se encontró con el loop
        if ($cantidad === null) {
            $cantidadNode = $xml->xpath('//cac:DespatchLine/cbc:DeliveredQuantity');
            if (!empty($cantidadNode)) {
                $attrs = $cantidadNode[0]->attributes();
                $unit = isset($attrs['unitCode']) ? strtoupper((string) $attrs['unitCode']) : '';
                $val = (float) (string) $cantidadNode[0];
                if (in_array($unit, ['BX', 'NIU', 'ZZ', 'CAJA', 'C62', 'PACK', 'CT'], true)) {
                    $cajas = (int) round($val);
                } else {
                    $cantidad = $val;
                    $unidadMedida = $unit ?: null;
                }
            }
        }

        // Intentar obtener bultos de TransportHandlingUnit si existe
        if ($cajas === null) {
            $pkgQty = $xml->xpath('//cac:Shipment/cac:TransportHandlingUnit/cac:Package/cbc:Quantity');
            if (!empty($pkgQty)) {
                $cajas = (int) round((float) (string) $pkgQty[0]);
            }
        }

        $detalle = $this->xpathValue($xml, '//cac:DespatchLine/cac:Item/cbc:Description');

        $kgCaja = null;
        if ($detalle && preg_match('/EN CAJAS DE (\d+)\s*KG/i', $detalle, $m)) {
            $kgCaja = (int) $m[1];
        }

        // Si tenemos kgCaja y cantidad en TNE pero no cajas, calcularlo
        if ($cajas === null && $kgCaja !== null && $cantidad !== null) {
            $cajas = (int) round($cantidad * 1000 / $kgCaja);
        }

        $tipoOperacion = null;
        if ($detalle) {
            if (preg_match('/MAR[IÍ]TIMA|MARITIM[AO]/i', $detalle)) {
                $tipoOperacion = 'MARITIMO';
            } elseif (stripos($detalle, 'TERRESTRE') !== false) {
                $tipoOperacion = 'TERRESTRE';
            }
        }

        $contenedor = null;
        if ($detalle && preg_match('/CONTENEDOR\s+N[°o°]?:?\s*([A-Z0-9\s\-]+)/i', $detalle, $m)) {
            $contenedor = trim($m[1]);
        }

        // Referencia a la factura que origina esta guía
        $facturaReferencia = null;
        $docRefs = $xml->xpath('//cac:AdditionalDocumentReference');
        foreach ($docRefs as $ref) {
            $ref->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $typeNodes = $ref->xpath('cbc:DocumentTypeCode');
            $typeCode = !empty($typeNodes) ? (string) $typeNodes[0] : '';
            if ($typeCode === '01' || $typeCode === '03') {
                $idNodes = $ref->xpath('cbc:ID');
                $refId = !empty($idNodes) ? trim((string) $idNodes[0]) : '';
                if ($refId !== '') {
                    $facturaReferencia = $refId;
                    break;
                }
            }
        }
        if (!$facturaReferencia) {
            $facturaReferencia = $this->xpathValue($xml, '//cac:OrderReference/cbc:ID');
        }

        return [
            'tipoDocumento' => '09',
            'serie' => $serie,
            'correlativo' => $correlativo,
            'numeroDocumento' => $id,
            'fechaEmision' => $fechaEmision,
            'rucCliente' => $rucCliente,
            'nombreCliente' => $nombreCliente,
            'detalle' => $detalle,
            'cantidad' => $cantidad,
            'unidadMedida' => $unidadMedida,
            'cajas' => $cajas,
            'kgCaja' => $kgCaja,
            'tipoOperacion' => $tipoOperacion,
            'contenedor' => $contenedor,
            'facturaReferencia' => $facturaReferencia,
        ];
    }

    private function parseDebitNote(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $id = $this->xpathValue($xml, '//cbc:ID');
        $parts = explode('-', $id ?? '', 2);
        $serie = $parts[0] ?? '';
        $correlativo = $parts[1] ?? '';

        $fechaEmision = $this->xpathValue($xml, '//cbc:IssueDate');
        $moneda = $this->xpathValue($xml, '//cbc:DocumentCurrencyCode');

        $rucCliente = null;
        $partyIds = $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID');
        foreach ($partyIds as $pid) {
            $attrs = $pid->attributes();
            if (isset($attrs['schemeID']) && (string) $attrs['schemeID'] === '6') {
                $rucCliente = (string) $pid;
                break;
            }
        }

        $nombreCliente = $this->xpathValue($xml, '//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName');

        // Documento de referencia (factura original que origina la nota de débito)
        $facturaReferencia = $this->xpathValue($xml, '//cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID');

        // Líneas de la nota de débito
        $debitLines = $xml->xpath('//cac:DebitNoteLine');
        $items = [];

        foreach ($debitLines as $line) {
            $line->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $line->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

            $lineCantidad = null;
            $lineUnidad = null;
            $qtyNodes = $line->xpath('cbc:DebitedQuantity');
            if (!empty($qtyNodes)) {
                $lineCantidad = (float)(string)$qtyNodes[0];
                $attrs = $qtyNodes[0]->attributes();
                $lineUnidad = isset($attrs['unitCode']) ? (string)$attrs['unitCode'] : null;
            }

            $lineImporte = null;
            $importeNodes = $line->xpath('cbc:LineExtensionAmount');
            if (!empty($importeNodes)) {
                $lineImporte = (float)(string)$importeNodes[0] ?: null;
            }

            $lineIgv = null;
            $igvNodes = $line->xpath('cac:TaxTotal/cbc:TaxAmount');
            if (!empty($igvNodes)) {
                $lineIgv = (float)(string)$igvNodes[0];
            }

            $lineTotal = $lineImporte !== null ? $lineImporte + ($lineIgv ?? 0.0) : null;

            $lineVU = null;
            $vuNodes = $line->xpath('cac:Price/cbc:PriceAmount');
            if (!empty($vuNodes)) {
                $lineVU = (float)(string)$vuNodes[0] ?: null;
            }

            $lineDetalle = null;
            $descNodes = $line->xpath('cac:Item/cbc:Description');
            if (!empty($descNodes)) {
                $lineDetalle = trim((string)$descNodes[0]) ?: null;
            }

            $items[] = [
                'cantidad'      => $lineCantidad,
                'unidadMedida'  => $lineUnidad,
                'valorUnitario' => $lineVU,
                'importe'       => $lineImporte,
                'igv'           => $lineIgv,
                'total'         => $lineTotal,
                'detalle'       => $lineDetalle,
                'kgCaja'        => null,
                'cajas'         => null,
                'tipoOperacion' => null,
                'contenedor'    => null,
                'tipoServicio'  => null,
            ];
        }

        // Fallback si no hay líneas
        if (empty($items)) {
            $items[] = [
                'cantidad'      => null,
                'unidadMedida'  => null,
                'valorUnitario' => null,
                'importe'       => (float)$this->xpathValue($xml, '//cac:RequestedMonetaryTotal/cbc:LineExtensionAmount') ?: null,
                'igv'           => (float)$this->xpathValue($xml, '//cac:TaxTotal/cbc:TaxAmount') ?: null,
                'total'         => (float)$this->xpathValue($xml, '//cac:RequestedMonetaryTotal/cbc:PayableAmount') ?: null,
                'detalle'       => null,
                'kgCaja'        => null,
                'cajas'         => null,
                'tipoOperacion' => null,
                'contenedor'    => null,
                'tipoServicio'  => null,
            ];
        }

        $first = $items[0];

        return [
            'tipoDocumento'   => '08',
            'serie'           => $serie,
            'correlativo'     => $correlativo,
            'numeroDocumento' => $id,
            'fechaEmision'    => $fechaEmision,
            'moneda'          => $moneda ?? 'USD',
            'rucCliente'      => $rucCliente,
            'nombreCliente'   => $nombreCliente,
            'facturaReferencia' => $facturaReferencia,
            'items'           => $items,
            'detalle'         => $first['detalle'],
            'cantidad'        => $first['cantidad'],
            'unidadMedida'    => $first['unidadMedida'],
            'valorUnitario'   => $first['valorUnitario'],
            'importe'         => $first['importe'],
            'igv'             => $first['igv'],
            'total'           => $first['total'],
            'kgCaja'          => $first['kgCaja'],
            'cajas'           => $first['cajas'],
            'tipoOperacion'   => $first['tipoOperacion'],
            'contenedor'      => $first['contenedor'],
            'tipoServicio'    => $first['tipoServicio'],
        ];
    }

    private function xpathValue(\SimpleXMLElement $xml, string $path): ?string
    {
        $result = $xml->xpath($path);
        if (empty($result)) {
            return null;
        }
        $value = trim((string) $result[0]);
        return $value !== '' ? $value : null;
    }

    private function normalizarNumeroGuia(string $raw): string
    {
        if (preg_match('/^([A-Z]{1,4})(0*)(\d+)$/i', str_replace('-', '', $raw), $m)) {
            return $m[1] . '-' . ltrim($m[2] . $m[3], '0');
        }
        $parts = explode('-', $raw, 2);
        if (count($parts) === 2) {
            return $parts[0] . '-' . ltrim($parts[1], '0');
        }
        return $raw;
    }
}
