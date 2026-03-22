<?php

namespace App\apps\security\Service\Auth;

use App\shared\Service\Dto\DtoRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TokenCheckDto implements DtoRequestInterface
{
    public function __construct(
        #[Assert\NotBlank]
        public string $token,
    ) {
    }
}
