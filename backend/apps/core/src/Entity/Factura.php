<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\FacturaRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturaRepository::class)]
#[ORM\Table(name: 'core_factura')]
#[ORM\HasLifecycleCallbacks]
class Factura
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 2)]
    private ?string $tipoDocumento = null;

    #[ORM\Column(length: 4)]
    private ?string $serie = null;

    #[ORM\Column(length: 10)]
    private ?string $correlativo = null;

    #[ORM\Column(length: 20)]
    private ?string $numeroDocumento = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numeroGuia = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fechaEmision = null;

    #[ORM\Column(length: 3, options: ['default' => 'USD'])]
    private string $moneda = 'USD';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $detalle = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $kgCaja = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $unidadMedida = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cajas = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, nullable: true)]
    private ?string $cantidad = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, nullable: true)]
    private ?string $valorUnitario = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $importe = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $igv = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $total = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3, nullable: true)]
    private ?string $tipoCambio = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tipoServicio = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tipoOperacion = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isAnulada = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contenedor = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $destino = null;

    #[ORM\ManyToOne(targetEntity: Despacho::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Despacho $despacho = null;

    public function getId(): ?int { return $this->id; }

    public function getTipoDocumento(): ?string { return $this->tipoDocumento; }
    public function setTipoDocumento(string $v): static { $this->tipoDocumento = $v; return $this; }

    public function getSerie(): ?string { return $this->serie; }
    public function setSerie(string $v): static { $this->serie = $v; return $this; }

    public function getCorrelativo(): ?string { return $this->correlativo; }
    public function setCorrelativo(string $v): static { $this->correlativo = $v; return $this; }

    public function getNumeroDocumento(): ?string { return $this->numeroDocumento; }
    public function setNumeroDocumento(string $v): static { $this->numeroDocumento = $v; return $this; }

    public function getNumeroGuia(): ?string { return $this->numeroGuia; }
    public function setNumeroGuia(?string $v): static { $this->numeroGuia = $v; return $this; }

    public function getFechaEmision(): ?\DateTimeInterface { return $this->fechaEmision; }
    public function setFechaEmision(\DateTimeInterface $v): static { $this->fechaEmision = $v; return $this; }

    public function getMoneda(): string { return $this->moneda; }
    public function setMoneda(string $v): static { $this->moneda = $v; return $this; }

    public function getDetalle(): ?string { return $this->detalle; }
    public function setDetalle(?string $v): static { $this->detalle = $v; return $this; }

    public function getKgCaja(): ?int { return $this->kgCaja; }
    public function setKgCaja(?int $v): static { $this->kgCaja = $v; return $this; }

    public function getUnidadMedida(): ?string { return $this->unidadMedida; }
    public function setUnidadMedida(?string $v): static { $this->unidadMedida = $v; return $this; }

    public function getCajas(): ?int { return $this->cajas; }
    public function setCajas(?int $v): static { $this->cajas = $v; return $this; }

    public function getCantidad(): ?string { return $this->cantidad; }
    public function setCantidad(string|float|null $v): static { $this->cantidad = $v !== null ? (string) $v : null; return $this; }

    public function getValorUnitario(): ?string { return $this->valorUnitario; }
    public function setValorUnitario(string|float|null $v): static { $this->valorUnitario = $v !== null ? (string) $v : null; return $this; }

    public function getImporte(): ?string { return $this->importe; }
    public function setImporte(string|float|null $v): static { $this->importe = $v !== null ? (string) $v : null; return $this; }

    public function getIgv(): ?string { return $this->igv; }
    public function setIgv(string|float|null $v): static { $this->igv = $v !== null ? (string) $v : null; return $this; }

    public function getTotal(): ?string { return $this->total; }
    public function setTotal(string|float|null $v): static { $this->total = $v !== null ? (string) $v : null; return $this; }

    public function getTipoCambio(): ?string { return $this->tipoCambio; }
    public function setTipoCambio(string|float|null $v): static { $this->tipoCambio = $v !== null ? (string) $v : null; return $this; }

    public function getTipoServicio(): ?string { return $this->tipoServicio; }
    public function setTipoServicio(?string $v): static { $this->tipoServicio = $v; return $this; }

    public function getTipoOperacion(): ?string { return $this->tipoOperacion; }
    public function setTipoOperacion(?string $v): static { $this->tipoOperacion = $v; return $this; }

    public function isAnulada(): bool { return $this->isAnulada; }
    public function setIsAnulada(bool $v): static { $this->isAnulada = $v; return $this; }

    public function getContenedor(): ?string { return $this->contenedor; }
    public function setContenedor(?string $v): static { $this->contenedor = $v; return $this; }

    public function getDestino(): ?string { return $this->destino; }
    public function setDestino(?string $v): static { $this->destino = $v; return $this; }

    public function getDespacho(): ?Despacho { return $this->despacho; }
    public function setDespacho(?Despacho $despacho): static { $this->despacho = $despacho; return $this; }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
