<?php

namespace App\shared\Exception;

use Symfony\Component\HttpFoundation\Response;

final class NotFoundException extends DomainException
{
    public function __construct(string $message = 'Not found', int $code = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($message, $code);
    }
}