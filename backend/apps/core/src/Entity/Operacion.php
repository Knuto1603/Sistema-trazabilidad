<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\OperacionRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperacionRepository::class)]
#[ORM\Table(name: 'core_operacion')]
#[ORM\HasLifecycleCallbacks]
class Operacion
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(length: 20)]
    private ?string $sede = null;

    public function getId(): ?int { return $this->id; }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(string $v): static { $this->nombre = $v; return $this; }

    public function getSede(): ?string { return $this->sede; }
    public function setSede(string $v): static { $this->sede = $v; return $this; }

    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
