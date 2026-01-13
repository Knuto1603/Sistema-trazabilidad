<?php

namespace App\Security\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: "roles")]
class Role
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $nombre = null; // Ej: ROLE_ADMIN

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    public function __construct(string $nombre)
    {
        $this->id = Uuid::v4();
        $this->nombre = $nombre;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->nombre;
    }
}
