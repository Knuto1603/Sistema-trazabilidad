<?php

namespace App\shared\Service\Sorting;

use Doctrine\ORM\QueryBuilder;
use App\Shared\Service\Dto\SortingDto;

readonly class SortByRequestField implements SortingStrategy
{
    public function __construct(
        private ?SortingDto $sorting,
        private array $fields, // ['request_field' => 'database_field']
    ) {
    }

    public function apply(QueryBuilder $queryBuilder): void
    {
        if ($this->sorting === null) {
            return;
        }

        $requestedField = $this->sorting->field;
        if (array_key_exists($requestedField, $this->fields)) {
            $databaseField = $this->fields[$requestedField];
            $queryBuilder->addOrderBy($databaseField, $this->sorting->direction);
        }
    }
}