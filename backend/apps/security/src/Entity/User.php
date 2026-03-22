<?php

namespace App\apps\security\Entity;

use App\shared\Doctrine\UidType;
use App\shared\Entity\EntityTrait;
use App\shared\Service\Helper;
use App\apps\security\Entity\AttachFile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\apps\security\Repository\UserRepository;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\AbstractUid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'security_user')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use EntityTrait;

    const ROLE_USER = 'ROLE_USER';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    protected ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $username = null;

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(length: 100)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(type: UidType::NAME, nullable: true)]
    private ?AbstractUid $gender = null;

    #[ORM\OneToOne(targetEntity: AttachFile::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?AttachFile $photo = null;

    /**
     * @var Collection<int, UserRole>
     */
    #[ORM\ManyToMany(targetEntity: UserRole::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'security_user_user_role')]
    private Collection $rol;

    public function __construct()
    {
        $this->rol = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = Helper::slugLower($username);

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->uuidToString();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = [];

        // Convertir los objetos UserRole a strings
        foreach ($this->rol as $role) {
            if ($role->isActive()) {
                $roles[] = $role->getName();
            }
        }

        // Si el usuario no tiene roles, se le debe asignar ROLE_USER
        if (empty($roles)) {
            $roles[] = self::ROLE_USER;
        }

        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getGender(): ?AbstractUid
    {
        return $this->gender;
    }

    public function setGender(?AbstractUid $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getPhoto(): ?AttachFile
    {
        return $this->photo;
    }

    public function setPhoto(?AttachFile $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return Collection<int, UserRole>
     */
    public function getRol(): Collection
    {
        return $this->rol;
    }

    public function addRol(UserRole $rol): static
    {
        if (!$this->rol->contains($rol)) {
            $this->rol->add($rol);
        }

        return $this;
    }

    public function removeRol(UserRole $rol): static
    {
        $this->rol->removeElement($rol);

        return $this;
    }
}
