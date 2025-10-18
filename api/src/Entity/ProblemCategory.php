<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProblemCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProblemCategoryRepository::class)]
#[ORM\Table(name: 'problem_category')]
#[ORM\Index(name: 'idx_problem_category_code', columns: ['code'])]
#[ORM\Index(name: 'idx_problem_category_priority', columns: ['priority'])]
#[ORM\HasLifecycleCallbacks]
class ProblemCategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 50)]
    #[Assert\Regex(pattern: '/^[a-z_]+$/', message: 'Code must contain only lowercase letters and underscores')]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    private string $name;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $priority = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, AdviceCard>
     */
    #[ORM\OneToMany(targetEntity: AdviceCard::class, mappedBy: 'category')]
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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
            $adviceCard->setCategory($this);
        }

        return $this;
    }

    public function removeAdviceCard(AdviceCard $adviceCard): self
    {
        if ($this->adviceCards->removeElement($adviceCard)) {
            if ($adviceCard->getCategory() === $this) {
                $adviceCard->setCategory(null);
            }
        }

        return $this;
    }
}
