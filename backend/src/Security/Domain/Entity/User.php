<?php

namespace App\Security\Domain\Entity;

use App\Shared\Domain\Entity\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: "usuarios")]
#[ORM\UniqueConstraint(name: "UNIQ_IDENTIFIER_USERNAME", fields: ["username"])]
class User extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $nombreCompleto = null;

    /**
     * Relación con la tabla de Roles mediante UID
     */
    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: "usuario_roles")]
    private Collection $rolesEntities;

    public function __construct()
    {
        parent::__construct();
        $this->id = Uuid::v4();
        $this->rolesEntities = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * Mapea las entidades Rol de la base de datos a los strings que espera Symfony Security
     */
    public function getRoles(): array
    {
        $roles = $this->rolesEntities->map(fn(Role $role) => $role->getNombre())->toArray();
        // Garantizamos que siempre tenga al menos el rol base
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function addRolEntity(Role $role): self
    {
        if (!$this->rolesEntities->contains($role)) {
            $this->rolesEntities->add($role);
        }
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getNombreCompleto(): ?string
    {
        return $this->nombreCompleto;
    }

    public function setNombreCompleto(string $nombreCompleto): self
    {
        $this->nombreCompleto = $nombreCompleto;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Limpieza de datos sensibles temporales
    }
}
