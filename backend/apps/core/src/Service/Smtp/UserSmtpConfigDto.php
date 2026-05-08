<?php

namespace App\apps\core\Service\Smtp;

use Symfony\Component\Validator\Constraints as Assert;

final class UserSmtpConfigDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'El email SMTP es requerido.')]
        #[Assert\Email(message: 'El email SMTP no es válido.')]
        #[Assert\Length(max: 150)]
        public ?string $smtpEmail = null,

        #[Assert\Length(min: 4, max: 100, minMessage: 'La contraseña debe tener al menos 4 caracteres.')]
        public ?string $smtpPassword = null,
    ) {
    }
}
