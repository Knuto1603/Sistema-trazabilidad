<?php

namespace App\apps\core\Service\Voucher;

use App\apps\core\Entity\Voucher;
use App\apps\core\Repository\VoucherRepository;
use App\apps\core\Service\Voucher\Dto\VoucherDtoTransformer;

readonly class SearchVouchersService
{
    public function __construct(
        private VoucherRepository $voucherRepository,
        private VoucherDtoTransformer $transformer,
    ) {}

    public function execute(string $clienteId, string $q = ''): array
    {
        $vouchers = $this->voucherRepository->searchDisponibles($clienteId, $q);
        $disponibles = array_filter($vouchers, fn(Voucher $v) => $v->getMontoRestante() > 0.001);
        return $this->transformer->fromObjects(array_values($disponibles));
    }
}
