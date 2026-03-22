<?php

namespace App\apps\core\Controller;

use App\apps\core\Service\Parametro\ChangeStateParametroService;
use App\apps\core\Service\Parametro\CreateParametroService;
use App\apps\core\Service\Parametro\DeleteParametroService;
use App\apps\core\Service\Parametro\DownloadParametrosService;
use App\apps\core\Service\Parametro\Dto\ParametroDto;
use App\apps\core\Service\Parametro\Dto\ParametroDtoTransformer;
use App\apps\core\Service\Parametro\Filter\ParametroFilterDto;
use App\apps\core\Service\Parametro\GetParametroService;
use App\apps\core\Service\Parametro\GetParametrosFilterAdvancedService;
use App\apps\core\Service\Parametro\GetParametrosService;
use App\apps\core\Service\Parametro\GetParentsParametroService;
use App\apps\core\Service\Parametro\GetSharedParametroService;
use App\apps\core\Service\Parametro\UpdateParametroService;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/parametros')]
final class ParametroApi extends AbstractSerializerApi
{
    #[Route('/', name: 'parametro_list', methods: ['GET'])]
    public function list(
        #[MapQueryString]
        FilterDto $filterDto,
        GetParametrosService $parametrosService,
    ): Response {
        return $this->ok($parametrosService->execute($filterDto));
    }

    #[Route('/filter_advanced', name: 'parametro_filter_advanced', methods: ['GET'])]
    public function filterAdvanced(
        #[MapQueryString]
        ParametroFilterDto $filterDto,
        GetParametrosFilterAdvancedService $parametrosService,
    ): Response {
        return $this->ok($parametrosService->execute($filterDto));
    }

    #[Route('/', name: 'parametro_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        ParametroDto $parametroDto,
        CreateParametroService $parametroService,
        ParametroDtoTransformer $transformer,
    ): Response {
        $parametro = $parametroService->execute($parametroDto);

        return $this->ok([
            'message' => 'Registro creado',
            'item' => $transformer->fromObject($parametro),
        ]);
    }

    #[Route('/{id}', name: 'parametro_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(
        #[MapRequestPayload]
        ParametroDto $parametroDto,
        string $id,
        UpdateParametroService $parametroService,
        ParametroDtoTransformer $transformer,
    ): Response {
        $parametro = $parametroService->execute($id, $parametroDto);

        return $this->ok([
            'message' => 'Registro actualizado',
            'item' => $transformer->fromObject($parametro),
        ]);
    }

    #[Route('/{id}', name: 'parametro_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(
        string $id,
        GetParametroService $parametroService,
        ParametroDtoTransformer $transformer,
    ): Response {
        $parametro = $parametroService->execute($id, true);

        return $this->ok([
            'message' => 'Registro obtenido',
            'item' => $transformer->fromObject($parametro),
        ]);
    }

    #[Route('/{id}/disable', name: 'parametro_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function disable(
        string $id,
        ChangeStateParametroService $parametroService,
        ParametroDtoTransformer $transformer,
    ): Response {
        $parametro = $parametroService->execute($id, false);

        return $this->ok([
            'message' => 'Registro deshabilitado',
            'item' => $transformer->fromObject($parametro),
        ]);
    }

    #[Route('/{id}/enable', name: 'parametro_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function enable(
        string $id,
        ChangeStateParametroService $parametroService,
        ParametroDtoTransformer $transformer,
    ): Response {
        $parametro = $parametroService->execute($id, true);

        return $this->ok([
            'message' => 'Registro habilitado',
            'item' => $transformer->fromObject($parametro),
        ]);
    }

    #[Route('/{id}', name: 'parametro_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(
        string $id,
        DeleteParametroService $parametroService,
    ): Response {
        $parametroService->execute($id);

        return $this->ok([
            'message' => 'Registro borrado',
            'item' => null,
        ]);
    }

    #[Route('/download', name: 'parametro_download', methods: ['GET'])]
    public function __invoke(
        #[MapQueryString]
        FilterDto $filterDto,
        DownloadParametrosService $parametrosService,
    ): Response {
        return $parametrosService->execute($filterDto);
    }

    #[Route('/parents', name: 'parametro_list_parents', methods: ['GET'])]
    public function listParents(
        GetParentsParametroService $parametroService,
    ): Response {
        return $this->ok(['items' => $parametroService->execute()]);
    }

    #[Route('/shared', name: 'parametro_list_shared', methods: ['GET'])]
    public function listShared(
        GetSharedParametroService $parametroService,
    ): Response {
        return $this->ok(['items' => $parametroService->execute()]);
    }
}
