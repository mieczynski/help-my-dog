<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AdviceCardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdviceCardRepository::class)]
#[ORM\Table(name: 'advice_card')]
#[ORM\Index(name: 'idx_advice_card_dog_id', columns: ['dog_id'])]
#[ORM\Index(name: 'idx_advice_card_category_id', columns: ['category_id'])]
#[ORM\Index(name: 'idx_advice_card_type', columns: ['advice_type'])]
#[ORM\Index(name: 'idx_advice_card_dog_date', columns: ['dog_id', 'created_at'])]
#[ORM\Index(name: 'idx_advice_card_dog_rating', columns: ['dog_id', 'rating'])]
#[ORM\HasLifecycleCallbacks]
class AdviceCard
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Dog::class, inversedBy: 'adviceCards')]
    #[ORM\JoinColumn(name: 'dog_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Dog $dog = null;

    #[ORM\ManyToOne(targetEntity: ProblemCategory::class, inversedBy: 'adviceCards')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull]
    private ?ProblemCategory $category = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $problemDescription;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $aiResponse;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $planContent = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['quick', 'plan_7_days'])]
    private string $adviceType;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(choices: ['helpful', 'not_helpful'])]
    private ?string $rating = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
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

    public function getDog(): ?Dog
    {
        return $this->dog;
    }

    public function setDog(?Dog $dog): self
    {
        $this->dog = $dog;

        return $this;
    }

    public function getCategory(): ?ProblemCategory
    {
        return $this->category;
    }

    public function setCategory(?ProblemCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getProblemDescription(): string
    {
        return $this->problemDescription;
    }

    public function setProblemDescription(string $problemDescription): self
    {
        $this->problemDescription = $problemDescription;

        return $this;
    }

    public function getAiResponse(): string
    {
        return $this->aiResponse;
    }

    public function setAiResponse(string $aiResponse): self
    {
        $this->aiResponse = $aiResponse;

        return $this;
    }

    public function getPlanContent(): ?array
    {
        return $this->planContent;
    }

    public function setPlanContent(?array $planContent): self
    {
        $this->planContent = $planContent;

        return $this;
    }

    public function getAdviceType(): string
    {
        return $this->adviceType;
    }

    public function setAdviceType(string $adviceType): self
    {
        $this->adviceType = $adviceType;

        return $this;
    }

    public function getRating(): ?string
    {
        return $this->rating;
    }

    public function setRating(?string $rating): self
    {
        $this->rating = $rating;

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
}
