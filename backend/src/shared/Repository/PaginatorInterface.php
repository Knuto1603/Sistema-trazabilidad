<?php

namespace App\shared\Repository;

use Countable;
use IteratorAggregate;

/**
 * @template T of object
 *
 * @extends IteratorAggregate<T>
 */
interface PaginatorInterface extends \IteratorAggregate, Countable
{
    public function currentPage(): int;

    public function itemsPerPage(): int;

    public function lastPage(): int;

    public function totalItems(): int;

    public function pagination(): array;

}