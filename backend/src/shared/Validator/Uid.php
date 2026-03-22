<?php

declare(strict_types=1);

namespace App\shared\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Uid extends Constraint
{
    public string $message = 'La cadena "{{ string }}" no tiene un formato válido de UID.';

    public function __construct(?string $message = null, ?array $groups = null, $payload = null)
    {
        parent::__construct([], $groups, $payload);
        $this->message = $message ?? $this->message;
    }
}
