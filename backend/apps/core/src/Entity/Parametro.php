<?php

namespace App\apps\core\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\shared\Entity\EntityTrait;

#[ORM\Entity(repositoryClass: ParametroRepository::class)]
#[ORM\Table(name: 'core_parametro')]
#[ORM\HasLifecycleCallbacks]
class Parametro implements \Stringable
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $alias = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $value = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $parent = null;

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): static
    {
        $this->alias = $alias ? mb_strtoupper($alias) : null;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
