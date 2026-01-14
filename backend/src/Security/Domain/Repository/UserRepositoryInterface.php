<?php

namespace App\Security\Domain\Repository;

use App\Security\Domain\Entity\User;

/**
 * Interfaz de dominio para el repositorio de usuarios.
 * No depende de Doctrine, solo de la entidad User.
 */
interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(string $id): ?User;

    public function findByUsername(string $username): ?User;

    public function delete(User $user): void;
}
