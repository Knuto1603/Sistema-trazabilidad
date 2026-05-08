<?php

namespace App\apps\core\Entity;

use App\apps\core\Repository\UserSmtpConfigRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSmtpConfigRepository::class)]
#[ORM\Table(name: 'core_user_smtp_config')]
#[ORM\Index(columns: ['user_uuid'], name: 'idx_smtp_config_user_uuid')]
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
    private string $smtpEmail;

    #[ORM\Column(length: 500)]
    private string $smtpPasswordEncrypted;

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
}
