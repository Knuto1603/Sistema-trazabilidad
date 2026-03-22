<?php

namespace App\shared\Exception;

use Symfony\Component\HttpFoundation\Response;

class RepositoryException extends DomainException
{
    public function __construct(string $message, int $code = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($message, $code);
    }
}
