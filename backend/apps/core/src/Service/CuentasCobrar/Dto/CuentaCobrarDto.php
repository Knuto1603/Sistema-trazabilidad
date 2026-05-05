<?php

namespace App\apps\core\Service\CuentasCobrar\Dto;

final class CuentaCobrarDto
{
    public ?string $id = null;
    public ?string $tipoDocumento = null;
    public ?string $numeroDocumento = null;
    public ?string $fechaEmision = null;
    public ?string $fechaVencimiento = null;
    public ?float $total = null;
    public string $moneda = 'USD';
    public ?string $contenedor = null;
    public ?string $numeroGuia = null;

    // Línea de factura
    public ?string $detalle = null;
    public ?int $cajas = null;
    public ?float $kgCaja = null;
    public ?float $importe = null;
    public ?float $igv = null;
    public ?float $tipoCambio = null;
    public ?string $tipoServicio = null;
    public ?string $destino = null;

    // Despacho
    public ?string $despachoId = null;
    public ?int $despachoNumero = null;
    public ?string $sede = null;

    // Cliente comercial (siempre el del despacho)
    public ?string $clienteId = null;
    public ?string $clienteRazonSocial = null;
    public ?string $clienteRuc = null;

    // Cliente de facturación (override fiscal, nullable)
    public ?string $clienteFacturaId = null;
    public ?string $clienteFacturaRazonSocial = null;
    public ?string $clienteFacturaRuc = null;

    // Operación
    public ?string $operacionId = null;
    public ?string $operacionNombre = null;

    // Computed payment status
    public float $montoPagado = 0.0;
    public float $montoPendiente = 0.0;
    public string $estado = 'PENDIENTE'; // PENDIENTE | PAGADO | VENCIDA

    // Pagos (activos e inactivos para historial)
    public array $pagos = [];
}
