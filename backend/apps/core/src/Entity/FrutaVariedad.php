<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\FrutaVariedadRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'core_fruta_variedad')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: FrutaVariedadRepository::class)]
class FrutaVariedad implements \Stringable
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\ManyToOne(targetEntity: Fruta::class)]
    #[ORM\JoinColumn(name: 'fruta_id', nullable: false)]
    private ?Fruta $fruta = null;

    public function __toString(): string
    {
        return $this->nombre ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getFruta(): ?Fruta
    {
        return $this->fruta;
    }

    public function setFruta(Fruta $fruta): static
    {
        $this->fruta = $fruta;
        return $this;
    }
}
