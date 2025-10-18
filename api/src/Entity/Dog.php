<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DogRepository::class)]
#[ORM\Table(name: 'dog')]
#[ORM\Index(name: 'idx_dog_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_dog_user_active', columns: ['user_id', 'deleted_at'])]
#[ORM\HasLifecycleCallbacks]
class Dog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'dogs')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $breed;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 300)]
    private int $ageMonths;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['male', 'female'])]
    private string $gender;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotNull]
    #[Assert\Range(min: 0.01, max: 200.00)]
    private string $weightKg;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['very_low', 'low', 'medium', 'high', 'very_high'])]
    private string $energyLevel;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * @var Collection<int, AdviceCard>
     */
    #[ORM\OneToMany(targetEntity: AdviceCard::class, mappedBy: 'dog', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $adviceCards;

    public function __construct()
    {
        $this->adviceCards = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBreed(): string
    {
        return $this->breed;
    }

    public function setBreed(string $breed): self
    {
        $this->breed = $breed;

        return $this;
    }

    public function getAgeMonths(): int
    {
        return $this->ageMonths;
    }

    public function setAgeMonths(int $ageMonths): self
    {
        $this->ageMonths = $ageMonths;

        return $this;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getWeightKg(): string
    {
        return $this->weightKg;
    }

    public function setWeightKg(string $weightKg): self
    {
        $this->weightKg = $weightKg;

        return $this;
    }

    public function getEnergyLevel(): string
    {
        return $this->energyLevel;
    }

    public function setEnergyLevel(string $energyLevel): self
    {
        $this->energyLevel = $energyLevel;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection<int, AdviceCard>
     */
    public function getAdviceCards(): Collection
    {
        return $this->adviceCards;
    }

    public function addAdviceCard(AdviceCard $adviceCard): self
    {
        if (!$this->adviceCards->contains($adviceCard)) {
            $this->adviceCards->add($adviceCard);
            $adviceCard->setDog($this);
        }

        return $this;
    }

    public function removeAdviceCard(AdviceCard $adviceCard): self
    {
        if ($this->adviceCards->removeElement($adviceCard)) {
            if ($adviceCard->getDog() === $this) {
                $adviceCard->setDog(null);
            }
        }

        return $this;
    }
}
