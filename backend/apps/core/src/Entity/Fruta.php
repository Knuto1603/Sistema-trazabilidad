<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\FrutaRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'core_fruta')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: FrutaRepository::class)]
class Fruta implements \Stringable
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(length: 5)]
    private ?string $codigo = null;

    public function __toString(): string
    {
        return $this->getNombre() ?? '';
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

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;
        return $this;
    }
}
