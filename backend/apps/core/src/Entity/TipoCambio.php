<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\TipoCambioRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TipoCambioRepository::class)]
#[ORM\Table(name: 'core_tipo_cambio')]
#[ORM\HasLifecycleCallbacks]
class TipoCambio
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, unique: true)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    private ?string $compra = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    private ?string $venta = null;

    public function getId(): ?int { return $this->id; }

    public function getFecha(): ?\DateTimeInterface { return $this->fecha; }
    public function setFecha(\DateTimeInterface $fecha): static { $this->fecha = $fecha; return $this; }

    public function getCompra(): ?string { return $this->compra; }
    public function setCompra(string|float $compra): static { $this->compra = (string) $compra; return $this; }

    public function getVenta(): ?string { return $this->venta; }
    public function setVenta(string|float $venta): static { $this->venta = (string) $venta; return $this; }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
