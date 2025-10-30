<?php

declare(strict_types=1);

namespace App\DTO\Response\Dog;

use App\Entity\Dog;

/**
 * Response DTO for dog profile.
 */
class DogResponseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $breed,
        public int $ageMonths,
        public string $gender,
        public float $weightKg,
        public string $energyLevel,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Creates Response DTO from Dog entity.
     */
    public static function fromEntity(Dog $dog): self
    {
        return new self(
            id: $dog->getId(),
            name: $dog->getName(),
            breed: $dog->getBreed(),
            ageMonths: $dog->getAgeMonths(),
            gender: $dog->getGender(),
            weightKg: (float) $dog->getWeightKg(),
            energyLevel: $dog->getEnergyLevel(),
            createdAt: $dog->getCreatedAt(),
            updatedAt: $dog->getUpdatedAt(),
        );
    }

    /**
     * Creates an array of Response DTOs from an array of Dog entities.
     */
    public static function fromEntities(array $dogs): array
    {
        return array_map(
            fn (Dog $dog): self => self::fromEntity($dog),
            $dogs
        );
    }
}
