<?php

namespace App\shared\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\shared\Doctrine\UidType;
use Symfony\Component\Uid\AbstractUid;

trait EntityTrait
{
    #[ORM\Column(type: UidType::NAME, unique: true)]
    protected ?AbstractUid $uuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $isActive = true;

    public function uuid(): ?AbstractUid
    {
        return $this->uuid;
    }

    public function uuidToString(): string
    {
        return UidType::toString($this->uuid);
    }

    public function createdAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function disable(): void
    {
        $this->isActive = false;
    }

    public function enable(): void
    {
        $this->isActive = true;
    }

    #[ORM\PrePersist]
    public function init(): void
    {
        $this->uuid = UidType::generate();
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist, ORM\PreUpdate]
    public function updatedDatetime(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
