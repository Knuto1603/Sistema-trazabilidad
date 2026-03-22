<?php

namespace App\shared\Service\Dto;

readonly class SortingDto
{
    public ?string $field;
    public string $direction;
    public function __construct(
        ?string $field,
        ?string $direction,
    ) {
        $this->field = $field ? strtolower($field) : null;
        $this->direction = $direction ? (strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC') : 'ASC';
    }

    public static function create(?string $field, ?string $direction): static
    {
        return new static($field, $direction);
    }
}