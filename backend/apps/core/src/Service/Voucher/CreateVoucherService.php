<?php

namespace App\apps\core\Service\Voucher;

use App\apps\core\Entity\Voucher;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\VoucherRepository;

readonly class CreateVoucherService
{
    public function __construct(
        private VoucherRepository $voucherRepository,
        private ClienteRepository $clienteRepository,
    ) {}

    public function execute(array $data): Voucher
    {
        $cliente = $this->clienteRepository->ofId($data['clienteId'], true);

        $voucher = new Voucher();
        $voucher->setNumero(trim($data['numero']));
        $voucher->setNumeroOperacion(!empty($data['numeroOperacion']) ? trim($data['numeroOperacion']) : null);
        $voucher->setMontoTotal((float) $data['montoTotal']);
        $voucher->setFecha(new \DateTime($data['fecha']));
        $voucher->setCliente($cliente);

        $this->voucherRepository->save($voucher);

        return $voucher;
    }
}
