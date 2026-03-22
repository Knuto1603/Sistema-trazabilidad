<?php

namespace App\shared\Exception;

use Symfony\Component\HttpFoundation\Response;

final class MissingParameterException extends DomainException
{
    public function __construct(string $message = 'Missing parameters', int $code = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($message, $code);
    }
}
