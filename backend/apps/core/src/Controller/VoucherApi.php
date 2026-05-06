<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Voucher\CreateVoucherService;
use App\apps\core\Service\Voucher\DeleteVoucherService;
use App\apps\core\Service\Voucher\Dto\VoucherDtoTransformer;
use App\apps\core\Service\Voucher\ForceDeleteVoucherService;
use App\apps\core\Service\Voucher\SearchVouchersService;
use App\apps\core\Service\Voucher\UpdateVoucherService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vouchers')]
class VoucherApi extends AbstractSerializerApi
{
    /** Lista paginada: GET /vouchers/?clienteId=xxx&q=xxx&page=0 (clienteId opcional) */
    #[Route('/', name: 'voucher_list', methods: ['GET'])]
    public function list(
        Request $request,
        \App\apps\core\Repository\VoucherRepository $voucherRepository,
        VoucherDtoTransformer $transformer,
    ): Response {
        $clienteId = $request->query->get('clienteId', '');
        $q     = $request->query->get('q', '');
        $page  = max(0, (int) $request->query->get('page', 0));
        $limit = 20;

        $result     = $voucherRepository->findPaginated($clienteId, $q, $page, $limit);
        $totalItems = $result['totalItems'];
        $offset     = $page * $limit;
        $items      = $transformer->fromObjects($result['items']);

        return $this->ok([
            'items'      => $items,
            'pagination' => [
                'page'         => $page,
                'itemsPerPage' => $limit,
                'count'        => count($items),
                'totalItems'   => $totalItems,
                'startIndex'   => $totalItems > 0 ? $offset + 1 : 0,
                'endIndex'     => min($offset + $limit, $totalItems),
            ],
        ]);
    }

    /** Crear voucher: POST /vouchers/ */
    #[Route('/', name: 'voucher_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateVoucherService $service,
        VoucherDtoTransformer $transformer,
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];

        foreach (['clienteId', 'numero', 'montoTotal', 'fecha'] as $field) {
            if (empty($data[$field])) {
                return $this->fail("El campo '{$field}' es requerido.");
            }
        }

        try {
            $voucher = $service->execute($data);

            return $this->ok([
                'message' => 'Voucher creado exitosamente',
                'item'    => $transformer->fromObject($voucher),
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }

    /** Editar voucher: PUT /vouchers/{id} */
    #[Route('/{id}', name: 'voucher_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        UpdateVoucherService $service,
        VoucherDtoTransformer $transformer,
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];

        foreach (['numero', 'montoTotal', 'fecha'] as $field) {
            if (empty($data[$field])) {
                return $this->fail("El campo '{$field}' es requerido.");
            }
        }

        try {
            $voucher = $service->execute($id, $data);

            return $this->ok([
                'message' => 'Voucher actualizado exitosamente',
                'item'    => $transformer->fromObject($voucher),
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Autocomplete: GET /vouchers/search?clienteId=xxx&q=partialNumero
     * Retorna vouchers del cliente con saldo disponible (montoRestante > 0)
     */
    #[Route('/search', name: 'voucher_search', methods: ['GET'])]
    public function search(
        Request $request,
        SearchVouchersService $service,
        VoucherDtoTransformer $transformer,
        \App\apps\core\Repository\VoucherRepository $voucherRepository,
    ): Response {
        $clienteId = $request->query->get('clienteId', '');
        $q = $request->query->get('q', '');
        $todos = filter_var($request->query->get('todos', 'false'), FILTER_VALIDATE_BOOLEAN);

        if (!$clienteId) {
            return $this->ok(['items' => []]);
        }

        if ($todos) {
            $vouchers = $voucherRepository->searchTodos($clienteId, $q);
            return $this->ok(['items' => $transformer->fromObjects($vouchers)]);
        }

        $items = $service->execute($clienteId, $q);

        return $this->ok(['items' => $items]);
    }

    /**
     * Obtener un voucher con su saldo: GET /vouchers/by-numero?clienteId=xxx&numero=yyy
     */
    #[Route('/by-numero', name: 'voucher_by_numero', methods: ['GET'])]
    public function byNumero(
        Request $request,
        \App\apps\core\Repository\VoucherRepository $voucherRepository,
        VoucherDtoTransformer $transformer,
    ): Response {
        $clienteId = $request->query->get('clienteId', '');
        $numero = $request->query->get('numero', '');

        if (!$clienteId || !$numero) {
            return $this->ok(['item' => null]);
        }

        // Necesitamos el cliente por UUID → int id
        // Búsqueda directa usando la query del repositorio
        $voucher = null;
        try {
            $vouchers = $voucherRepository->searchDisponibles($clienteId, $numero, 5);
            foreach ($vouchers as $v) {
                if ($v->getNumero() === $numero) {
                    $voucher = $v;
                    break;
                }
            }
        } catch (\Throwable) {
            // No encontrado
        }

        return $this->ok(['item' => $transformer->fromObject($voucher)]);
    }

    #[Route('/{id}', name: 'voucher_get', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function getOne(
        string $id,
        \App\apps\core\Repository\VoucherRepository $voucherRepository,
        VoucherDtoTransformer $transformer,
    ): Response {
        $voucher = $voucherRepository->findWithPagos($id);

        if ($voucher === null) {
            return $this->fail('Voucher no encontrado.', null, 404);
        }

        return $this->ok(['item' => $transformer->fromObjectWithPagos($voucher)]);
    }

    #[Route('/{id}', name: 'voucher_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(
        string $id,
        DeleteVoucherService $service,
    ): Response {
        try {
            $service->execute($id);

            return $this->ok(['message' => 'Voucher eliminado exitosamente', 'item' => null]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }

    #[Route('/{id}/force', name: 'voucher_force_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function forceDelete(
        string $id,
        Request $request,
        ForceDeleteVoucherService $service,
    ): Response {
        $data = json_decode($request->getContent(), true) ?? [];
        $justificante = $data['justificante'] ?? '';

        try {
            $service->execute($id, $justificante);

            return $this->ok(['message' => 'Voucher y sus pagos eliminados exitosamente', 'item' => null]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage());
        }
    }
}
