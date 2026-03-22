<?php

namespace App\shared\Exception;

class DomainException extends \RuntimeException
{
    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}
