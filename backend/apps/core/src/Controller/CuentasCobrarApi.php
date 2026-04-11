<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\CuentasCobrar\Filter\CuentasCobrarFilterDto;
use App\apps\core\Service\CuentasCobrar\GetCuentasCobrarService;
use App\shared\Api\AbstractSerializerApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cuentas-cobrar')]
class CuentasCobrarApi extends AbstractSerializerApi
{
    #[Route('/', name: 'cuentas_cobrar_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        CuentasCobrarFilterDto $filterDto,
        GetCuentasCobrarService $service,
    ): Response {
        return $this->ok($service->execute($filterDto));
    }
}
