<?php

namespace App\shared\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageApi extends AbstractApi
{
    #[Route(path: '/', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->ok(['health' => 'ok']);
    }
}
