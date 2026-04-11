<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\PagoFactura\CreatePagoFacturaService;
use App\apps\core\Service\PagoFactura\DeletePagoFacturaService;
use App\apps\core\Service\PagoFactura\Dto\PagoFacturaDtoTransformer;
use App\apps\core\Service\PagoFactura\GetPagosByFacturaService;
use App\apps\core\Service\PagoFactura\UpdatePagoFacturaService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pagos-factura')]
class PagoFacturaApi extends AbstractSerializerApi
{
    /** Todos los pagos (activos e inactivos) de una factura */
    #[Route('/by-factura/{id}', name: 'pago_factura_by_factura', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function byFactura(
        string $id,
        GetPagosByFacturaService $service,
    ): Response {
        return $this->ok(['items' => $service->execute($id)]);
    }

    /** Registrar un nuevo pago */
    #[Route('/', name: 'pago_factura_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreatePagoFacturaService $service,
        PagoFacturaDtoTransformer $transformer,
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];

        $required = ['facturaId', 'montoAplicado', 'voucherNumero'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->fail("El campo '{$field}' es requerido.");
            }
        }

        try {
            $pago = $service->execute([
                'facturaId'              => $data['facturaId'],
                'montoAplicado'          => (float) $data['montoAplicado'],
                'voucherNumero'          => trim($data['voucherNumero']),
                'voucherNumeroOperacion' => $data['voucherNumeroOperacion'] ?? null,
                'voucherMontoTotal'      => isset($data['voucherMontoTotal']) ? (float) $data['voucherMontoTotal'] : null,
                'voucherFecha'           => $data['voucherFecha'] ?? null,
            ]);

            return $this->ok([
                'message' => 'Pago registrado exitosamente',
                'item'    => $transformer->fromObject($pago),
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }

    /** Editar un pago */
    #[Route('/{id}', name: 'pago_factura_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        UpdatePagoFacturaService $service,
        PagoFacturaDtoTransformer $transformer,
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['montoAplicado'])) {
            return $this->fail("El campo 'montoAplicado' es requerido.");
        }

        try {
            $pago = $service->execute($id, [
                'montoAplicado'          => (float) $data['montoAplicado'],
                'voucherNumeroOperacion' => $data['voucherNumeroOperacion'] ?? null,
            ]);

            return $this->ok([
                'message' => 'Pago actualizado exitosamente',
                'item'    => $transformer->fromObject($pago),
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }

    /** Eliminar un pago (soft delete con justificante) */
    #[Route('/{id}', name: 'pago_factura_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(
        string $id,
        Request $request,
        DeletePagoFacturaService $service,
        PagoFacturaDtoTransformer $transformer,
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];
        $justificante = trim($data['justificante'] ?? '');

        try {
            $pago = $service->execute($id, $justificante);

            return $this->ok([
                'message' => 'Pago eliminado exitosamente',
                'item'    => $transformer->fromObject($pago),
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }
}
