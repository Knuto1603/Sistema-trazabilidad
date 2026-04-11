<?php

namespace App\apps\core\Service\Voucher;

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
        return $this->transformer->fromObjects($vouchers);
    }
}
