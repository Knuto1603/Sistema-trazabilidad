<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArchivoDespachoRepository::class)]
#[ORM\Table(name: 'core_archivo_despacho')]
#[ORM\HasLifecycleCallbacks]
class ArchivoDespacho
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 30)]
    private ?string $tipoArchivo = null;

    #[ORM\Column(length: 500)]
    private ?string $ruta = null;

    #[ORM\Column(type: 'integer')]
    private ?int $tamanho = null;

    #[ORM\ManyToOne(targetEntity: Despacho::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Despacho $despacho = null;

    #[ORM\ManyToOne(targetEntity: Factura::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Factura $factura = null;

    public function getId(): ?int { return $this->id; }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(string $v): static { $this->nombre = $v; return $this; }

    public function getTipoArchivo(): ?string { return $this->tipoArchivo; }
    public function setTipoArchivo(string $v): static { $this->tipoArchivo = $v; return $this; }

    public function getRuta(): ?string { return $this->ruta; }
    public function setRuta(string $v): static { $this->ruta = $v; return $this; }

    public function getTamanho(): ?int { return $this->tamanho; }
    public function setTamanho(int $v): static { $this->tamanho = $v; return $this; }

    public function getDespacho(): ?Despacho { return $this->despacho; }
    public function setDespacho(?Despacho $despacho): static { $this->despacho = $despacho; return $this; }

    public function getFactura(): ?Factura { return $this->factura; }
    public function setFactura(?Factura $factura): static { $this->factura = $factura; return $this; }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
