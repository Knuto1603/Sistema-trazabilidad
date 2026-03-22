<?php

namespace App\shared\Service\Filter;

use Doctrine\ORM\QueryBuilder;

class DoctrineSearchTextFilter
{
    public static function apply(QueryBuilder $queryBuilder, ?string $searchText, array $fields = []): void
    {
        if (null === $searchText || '' === $searchText || empty($fields)) {
            return;
        }

        $expression = $queryBuilder->expr();
        if (preg_match('/"([^"]+)"/', $searchText, $resultTexts)) {
            $separedWords[] = $resultTexts[1] ?? $resultTexts[0];
        } else {
            $separedWords = explode(' ', $searchText);
        }

        $connectorQuery = $expression->orX();
        foreach ($separedWords as $word) {
            if ('' === $word) {
                continue;
            }

            foreach ($fields as $field) {
                $connectorQuery->add($expression->like($field, $expression->literal('%'.$word.'%')));
            }

            $queryBuilder = $queryBuilder->andWhere($connectorQuery);
        }
    }
}
