<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Voucher\Dto\VoucherDtoTransformer;
use App\apps\core\Service\Voucher\SearchVouchersService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vouchers')]
class VoucherApi extends AbstractSerializerApi
{
    /**
     * Autocomplete: GET /vouchers/search?clienteId=xxx&q=partialNumero
     * Retorna vouchers del cliente con saldo disponible (montoRestante > 0)
     */
    #[Route('/search', name: 'voucher_search', methods: ['GET'])]
    public function search(
        Request $request,
        SearchVouchersService $service,
    ): Response {
        $clienteId = $request->query->get('clienteId', '');
        $q = $request->query->get('q', '');

        if (!$clienteId) {
            return $this->ok(['items' => []]);
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
}
