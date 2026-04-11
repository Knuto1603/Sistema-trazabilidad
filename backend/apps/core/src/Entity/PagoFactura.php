<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\PagoFacturaRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PagoFacturaRepository::class)]
#[ORM\Table(name: 'core_pago_factura')]
#[ORM\HasLifecycleCallbacks]
class PagoFactura
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Voucher::class, inversedBy: 'pagos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Voucher $voucher = null;

    #[ORM\ManyToOne(targetEntity: Factura::class, inversedBy: 'pagos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Factura $factura = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private ?string $montoAplicado = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $justificanteEliminacion = null;

    public function getId(): ?int { return $this->id; }

    public function getVoucher(): ?Voucher { return $this->voucher; }
    public function setVoucher(?Voucher $v): static { $this->voucher = $v; return $this; }

    public function getFactura(): ?Factura { return $this->factura; }
    public function setFactura(?Factura $v): static { $this->factura = $v; return $this; }

    public function getMontoAplicado(): ?string { return $this->montoAplicado; }
    public function setMontoAplicado(float|string $v): static { $this->montoAplicado = (string) $v; return $this; }

    public function getJustificanteEliminacion(): ?string { return $this->justificanteEliminacion; }
    public function setJustificanteEliminacion(?string $v): static { $this->justificanteEliminacion = $v; return $this; }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
