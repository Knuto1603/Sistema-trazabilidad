<?php

namespace App\apps\core\Service\Parametro;

use App\apps\core\Service\Parametro\Filter\ParentFilter;
use App\apps\core\Service\Parametro\Filter\ParametroFilterDto;
use App\shared\Service\Dto\FilterDto;


final readonly class GetParametrosFilterAdvancedService extends GetParametrosService
{
    protected function newFilters(FilterDto|ParametroFilterDto $filterDto): void
    {
        $this->filterService->addFilter(new ParentFilter($filterDto->parentId));
    }
}
