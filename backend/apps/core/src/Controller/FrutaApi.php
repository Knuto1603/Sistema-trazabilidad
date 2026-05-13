<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Fruta\ChangeStateFrutaService;
use App\apps\core\Service\Fruta\CreateFrutaService;
use App\apps\core\Service\Fruta\DownloadFrutasService;
use App\apps\core\Service\Fruta\Dto\FrutaDto;
use App\apps\core\Service\Fruta\Dto\FrutaDtoTransformer;
use App\apps\core\Service\Fruta\GetFrutasService;
use App\apps\core\Service\Fruta\GetFrutasShared;
use App\apps\core\Service\FrutaVariedad\ChangeStateFrutaVariedadService;
use App\apps\core\Service\FrutaVariedad\CreateFrutaVariedadService;
use App\apps\core\Service\FrutaVariedad\Dto\FrutaVariedadDto;
use App\apps\core\Service\FrutaVariedad\Dto\FrutaVariedadDtoTransformer;
use App\apps\core\Service\FrutaVariedad\GetFrutaVariedadesService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
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
        return $this->ok(['items' => $service->execute()]);
    }

    #[Route('/{id}/variedades', name: 'fruta_variedad_list', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function variedadesList(
        string $id,
        GetFrutaVariedadesService $service,
    ): Response
    {
        return $this->ok($service->execute($id));
    }

    #[Route('/{id}/variedades', name: 'fruta_variedad_create', requirements: ['id' => UidType::REGEX], methods: ['POST'])]
    public function variedadCreate(
        string $id,
        #[MapRequestPayload]
        FrutaVariedadDto $dto,
        CreateFrutaVariedadService $service,
        FrutaVariedadDtoTransformer $transformer,
    ): Response
    {
        $variedad = $service->execute($id, $dto);
        return $this->ok([
            'message' => 'Variedad creada',
            'item' => $transformer->fromObject($variedad),
        ]);
    }

    #[Route('/variedades/{id}/enable', name: 'fruta_variedad_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function variedadEnable(
        string $id,
        ChangeStateFrutaVariedadService $service,
        FrutaVariedadDtoTransformer $transformer,
    ): Response
    {
        $variedad = $service->execute($id, true);
        return $this->ok([
            'message' => 'Variedad habilitada',
            'item' => $transformer->fromObject($variedad),
        ]);
    }

    #[Route('/variedades/{id}/disable', name: 'fruta_variedad_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function variedadDisable(
        string $id,
        ChangeStateFrutaVariedadService $service,
        FrutaVariedadDtoTransformer $transformer,
    ): Response
    {
        $variedad = $service->execute($id, false);
        return $this->ok([
            'message' => 'Variedad deshabilitada',
            'item' => $transformer->fromObject($variedad),
        ]);
    }
}
