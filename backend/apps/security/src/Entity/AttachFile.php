<?php

declare(strict_types=1);

namespace App\apps\security\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'attach_file')]
class AttachFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 18, unique: true)]
    private ?string $secure = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $folder = null;

    #[ORM\Column(name: 'attach_directory', length: 24, nullable: true)]
    private ?string $attachDirectory = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSecure(): ?string
    {
        return $this->secure;
    }

    public function setSecure(?string $secure): static
    {
        $this->secure = $secure;
        return $this;
    }

    public function getFolder(): ?string
    {
        return $this->folder;
    }

    public function setFolder(?string $folder): static
    {
        $this->folder = $folder;
        return $this;
    }

    public function getAttachDirectory(): ?string
    {
        return $this->attachDirectory;
    }

    public function setAttachDirectory(?string $attachDirectory): static
    {
        $this->attachDirectory = $attachDirectory;
        return $this;
    }
}
