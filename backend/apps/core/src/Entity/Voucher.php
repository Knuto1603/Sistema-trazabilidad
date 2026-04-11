<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\VoucherRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoucherRepository::class)]
#[ORM\Table(name: 'core_voucher')]
#[ORM\UniqueConstraint(name: 'uq_voucher_numero_cliente', columns: ['numero', 'cliente_id'])]
#[ORM\HasLifecycleCallbacks]
class Voucher
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $numero = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $numeroOperacion = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private ?string $montoTotal = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\ManyToOne(targetEntity: Cliente::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cliente $cliente = null;

    #[ORM\OneToMany(targetEntity: PagoFactura::class, mappedBy: 'voucher')]
    private Collection $pagos;

    public function __construct()
    {
        $this->pagos = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $v): static { $this->numero = $v; return $this; }

    public function getNumeroOperacion(): ?string { return $this->numeroOperacion; }
    public function setNumeroOperacion(?string $v): static { $this->numeroOperacion = $v; return $this; }

    public function getMontoTotal(): ?string { return $this->montoTotal; }
    public function setMontoTotal(float|string $v): static { $this->montoTotal = (string) $v; return $this; }

    public function getFecha(): ?\DateTimeInterface { return $this->fecha; }
    public function setFecha(\DateTimeInterface $v): static { $this->fecha = $v; return $this; }

    public function getCliente(): ?Cliente { return $this->cliente; }
    public function setCliente(?Cliente $v): static { $this->cliente = $v; return $this; }

    public function getPagos(): Collection { return $this->pagos; }

    /** Monto disponible = montoTotal - suma de pagos activos */
    public function getMontoRestante(): float
    {
        $usado = 0.0;
        foreach ($this->pagos as $pago) {
            if ($pago->isActive()) {
                $usado += (float) $pago->getMontoAplicado();
            }
        }
        return (float) $this->montoTotal - $usado;
    }

    public function getMontoUsado(): float
    {
        $usado = 0.0;
        foreach ($this->pagos as $pago) {
            if ($pago->isActive()) {
                $usado += (float) $pago->getMontoAplicado();
            }
        }
        return $usado;
    }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
