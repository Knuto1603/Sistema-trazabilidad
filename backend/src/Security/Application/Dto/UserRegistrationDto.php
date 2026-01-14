<?php

namespace App\Security\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Objeto plano para recibir datos del registro/creación de usuarios.
 */
class UserRegistrationDto
{
    #[Assert\NotBlank(message: "El usuario es obligatorio")]
    public string $username;

    #[Assert\NotBlank(message: "La contraseña es obligatoria")]
    #[Assert\Length(min: 8, minMessage: "La contraseña debe tener al menos 8 caracteres")]
    public string $password;

    #[Assert\NotBlank(message: "El nombre completo es obligatorio")]
    public string $nombreCompleto;

    /** @var string[] */
    public array $roles = [];

    public function __construct(array $data)
    {
        $this->username = $data['username'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->nombreCompleto = $data['nombreCompleto'] ?? '';
        $this->roles = $data['roles'] ?? [];
    }
}
