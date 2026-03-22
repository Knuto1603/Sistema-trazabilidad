<?php

namespace App\shared\Service\Dto;

use App\shared\Validator;
trait DtoTrait
{
    #[Validator\Uid]
    public ?string $id; // Uuid

    public bool $isActive = true;

    public function ofEntity(mixed $object): void
    {
        $this->id = (string) $object->uuid()->toBase58();
        $this->isActive = $object->isActive();
    }
}