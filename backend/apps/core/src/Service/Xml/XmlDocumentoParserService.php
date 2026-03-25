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

        $cantidadNode = $xml->xpath('//cac:InvoiceLine/cbc:InvoicedQuantity');
        $cantidad = null;
        $unidadMedida = null;
        if (!empty($cantidadNode)) {
            $cantidad = (float) (string) $cantidadNode[0];
            $attrs = $cantidadNode[0]->attributes();
            $unidadMedida = isset($attrs['unitCode']) ? (string) $attrs['unitCode'] : null;
        }

        $detalle = $this->xpathValue($xml, '//cac:InvoiceLine/cac:Item/cbc:Description');
        $valorUnitario = (float) $this->xpathValue($xml, '//cac:InvoiceLine/cac:Price/cbc:PriceAmount');
        $importe = (float) $this->xpathValue($xml, '//cac:LegalMonetaryTotal/cbc:LineExtensionAmount');
        $igv = (float) $this->xpathValue($xml, '//cac:TaxTotal/cbc:TaxAmount');
        $total = (float) $this->xpathValue($xml, '//cac:LegalMonetaryTotal/cbc:PayableAmount');

        $kgCaja = null;
        if ($detalle && preg_match('/EN CAJAS DE (\d+)\s*KG/i', $detalle, $m)) {
            $kgCaja = (int) $m[1];
        }

        // Calcular cajas si tenemos kgCaja y cantidad en TNE
        $cajas = null;
        if ($kgCaja !== null && $cantidad !== null) {
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

        $tipoServicio = null;
        if ($detalle) {
            if (stripos($detalle, 'MAQUILA') !== false) {
                $tipoServicio = 'MAQUILA';
            } elseif (stripos($detalle, 'SOBRECOSTO') !== false || stripos($detalle, 'COMPLEMENTARIO') !== false) {
                $tipoServicio = 'SOBRECOSTO';
            } else {
                $tipoServicio = 'VENTA_CAJAS';
            }
        }

        return [
            'tipoDocumento' => $tipoDocumento ?? '01',
            'serie' => $serie,
            'correlativo' => $correlativo,
            'numeroDocumento' => $id,
            'numeroGuia' => $numeroGuia,
            'fechaEmision' => $fechaEmision,
            'moneda' => $moneda ?? 'USD',
            'rucCliente' => $rucCliente,
            'nombreCliente' => $nombreCliente,
            'detalle' => $detalle,
            'cantidad' => $cantidad,
            'unidadMedida' => $unidadMedida,
            'cajas' => $cajas,
            'valorUnitario' => $valorUnitario ?: null,
            'importe' => $importe ?: null,
            'igv' => $igv ?: null,
            'total' => $total ?: null,
            'kgCaja' => $kgCaja,
            'tipoOperacion' => $tipoOperacion,
            'contenedor' => $contenedor,
            'tipoServicio' => $tipoServicio,
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
