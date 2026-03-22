<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Fruta\ChangeStateFrutaService;
use App\apps\core\Service\Fruta\CreateFrutaService;
use App\apps\core\Service\Fruta\DownloadFrutasService;
use App\apps\core\Service\Fruta\Dto\FrutaDto;
use App\apps\core\Service\Fruta\Dto\FrutaDtoTransformer;
use App\apps\core\Service\Fruta\GetFrutasService;
use App\apps\core\Service\Fruta\GetFrutasShared;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/frutas')]
class FrutaApi extends AbstractSerializerApi
{
    #[Route('/', name: 'fruta_list', methods: ['GET'])]
    public function list(
        FilterDto $filterDto,
        GetFrutasService $service
    ): Response
    {
        return $this->ok($service->execute($filterDto));
    }

    #[Route('/', name: 'fruta_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        FrutaDto $frutaDto,
        FrutaDtoTransformer $transformer,
        CreateFrutaService $service,
    ): Response
    {
        $fruta = $service->execute($frutaDto);
        return $this->ok([
            'message' => 'Registro creado',
            'item' => $transformer->fromObject($fruta),
        ]);
    }

    #[Route('/{id}/disable', name: 'fruta_disable', methods: ['PATCH'])]
    public function disable(
        string $id,
        ChangeStateFrutaService $service,
        FrutaDtoTransformer $transformer,
    ): Response
    {
        $fruta = $service->execute($id, false);
        return $this->ok([
            'message' => 'Fruta deshabilitada',
            'item' => $transformer->fromObject($fruta),
        ]);
    }

    #[Route('/{id}/enable', name: 'fruta_enable', methods: ['PATCH'])]
    public function enable(
        string $id,
        ChangeStateFrutaService $service,
        FrutaDtoTransformer $transformer,
    ): Response
    {
        $fruta = $service->execute($id, true);
        return $this->ok([
            'message' => 'Fruta habilitada',
            'item' => $transformer->fromObject($fruta),
        ]);
    }

    #[Route('/download', name: 'fruta_download', methods: ['GET'])]
    public function __invoke(
        #[MapQueryString]
        FilterDto $filterDto,
        DownloadFrutasService $service,
    ): Response
    {
        return $service->execute($filterDto);
    }

    #[Route('/shared', name: 'fruta_shared_list', methods: ['GET'])]
    public function sharedList(
        GetFrutasShared $service
    ): Response
    {
        return $this->ok(['items'=>$service->execute()]);
    }



}
