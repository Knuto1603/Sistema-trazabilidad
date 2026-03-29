<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\DespachoRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DespachoRepository::class)]
#[ORM\Table(name: 'core_despacho')]
#[ORM\HasLifecycleCallbacks]
class Despacho
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $numeroCliente = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $numeroPlanta = null;

    #[ORM\Column(length: 20)]
    private ?string $sede = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contenedor = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observaciones = null;

    #[ORM\ManyToOne(targetEntity: Cliente::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cliente $cliente = null;

    #[ORM\ManyToOne(targetEntity: Fruta::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Fruta $fruta = null;

    #[ORM\ManyToOne(targetEntity: Operacion::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Operacion $operacion = null;

    public function getId(): ?int { return $this->id; }

    public function getNumeroCliente(): ?int { return $this->numeroCliente; }
    public function setNumeroCliente(int $v): static { $this->numeroCliente = $v; return $this; }

    public function getNumeroPlanta(): ?int { return $this->numeroPlanta; }
    public function setNumeroPlanta(?int $v): static { $this->numeroPlanta = $v; return $this; }

    public function getSede(): ?string { return $this->sede; }
    public function setSede(string $v): static { $this->sede = $v; return $this; }

    public function getContenedor(): ?string { return $this->contenedor; }
    public function setContenedor(?string $v): static { $this->contenedor = $v; return $this; }

    public function getObservaciones(): ?string { return $this->observaciones; }
    public function setObservaciones(?string $v): static { $this->observaciones = $v; return $this; }

    public function getCliente(): ?Cliente { return $this->cliente; }
    public function setCliente(?Cliente $cliente): static { $this->cliente = $cliente; return $this; }

    public function getFruta(): ?Fruta { return $this->fruta; }
    public function setFruta(?Fruta $fruta): static { $this->fruta = $fruta; return $this; }

    public function getOperacion(): ?Operacion { return $this->operacion; }
    public function setOperacion(?Operacion $operacion): static { $this->operacion = $operacion; return $this; }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
