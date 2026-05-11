<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\UserSmtpConfigRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSmtpConfigRepository::class)]
#[ORM\Table(name: 'core_user_smtp_config')]
#[ORM\HasLifecycleCallbacks]
class UserSmtpConfig
{
    use EntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 22, unique: true)]
    private string $userUuid = '';

    #[ORM\Column(length: 150)]
    private string $smtpEmail = '';

    #[ORM\Column(length: 500)]
    private string $smtpPasswordEncrypted = '';

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $firmaNombre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firmaCargo = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $firmaEmpresa = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ccEmails = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function setUserUuid(string $userUuid): static
    {
        $this->userUuid = $userUuid;
        return $this;
    }

    public function getSmtpEmail(): string
    {
        return $this->smtpEmail;
    }

    public function setSmtpEmail(string $smtpEmail): static
    {
        $this->smtpEmail = $smtpEmail;
        return $this;
    }

    public function getSmtpPasswordEncrypted(): string
    {
        return $this->smtpPasswordEncrypted;
    }

    public function setSmtpPasswordEncrypted(string $encrypted): static
    {
        $this->smtpPasswordEncrypted = $encrypted;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getFirmaNombre(): ?string
    {
        return $this->firmaNombre;
    }

    public function setFirmaNombre(?string $firmaNombre): static
    {
        $this->firmaNombre = $firmaNombre;
        return $this;
    }

    public function getFirmaCargo(): ?string
    {
        return $this->firmaCargo;
    }

    public function setFirmaCargo(?string $firmaCargo): static
    {
        $this->firmaCargo = $firmaCargo;
        return $this;
    }

    public function getFirmaEmpresa(): ?string
    {
        return $this->firmaEmpresa;
    }

    public function setFirmaEmpresa(?string $firmaEmpresa): static
    {
        $this->firmaEmpresa = $firmaEmpresa;
        return $this;
    }

    public function getCcEmails(): ?string
    {
        return $this->ccEmails;
    }

    public function setCcEmails(?string $ccEmails): static
    {
        $this->ccEmails = $ccEmails;
        return $this;
    }
}
