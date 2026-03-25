<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\ClienteRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClienteRepository::class)]
#[ORM\Table(name: 'core_cliente')]
#[ORM\HasLifecycleCallbacks]
class Cliente implements \Stringable
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 11, unique: true)]
    private ?string $ruc = null;

    #[ORM\Column(length: 255)]
    private ?string $razonSocial = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nombreComercial = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $departamento = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $provincia = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $distrito = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $estado = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $condicion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $tipoContribuyente = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    public function __toString(): string
    {
        return $this->razonSocial ?? '';
    }

    public function getId(): ?int { return $this->id; }

    public function getRuc(): ?string { return $this->ruc; }
    public function setRuc(string $ruc): static { $this->ruc = $ruc; return $this; }

    public function getRazonSocial(): ?string { return $this->razonSocial; }
    public function setRazonSocial(string $razonSocial): static { $this->razonSocial = $razonSocial; return $this; }

    public function getNombreComercial(): ?string { return $this->nombreComercial; }
    public function setNombreComercial(?string $v): static { $this->nombreComercial = $v; return $this; }

    public function getDireccion(): ?string { return $this->direccion; }
    public function setDireccion(?string $v): static { $this->direccion = $v; return $this; }

    public function getDepartamento(): ?string { return $this->departamento; }
    public function setDepartamento(?string $v): static { $this->departamento = $v; return $this; }

    public function getProvincia(): ?string { return $this->provincia; }
    public function setProvincia(?string $v): static { $this->provincia = $v; return $this; }

    public function getDistrito(): ?string { return $this->distrito; }
    public function setDistrito(?string $v): static { $this->distrito = $v; return $this; }

    public function getEstado(): ?string { return $this->estado; }
    public function setEstado(?string $v): static { $this->estado = $v; return $this; }

    public function getCondicion(): ?string { return $this->condicion; }
    public function setCondicion(?string $v): static { $this->condicion = $v; return $this; }

    public function getTipoContribuyente(): ?string { return $this->tipoContribuyente; }
    public function setTipoContribuyente(?string $v): static { $this->tipoContribuyente = $v; return $this; }

    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $v): static { $this->telefono = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): static { $this->email = $v; return $this; }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
