<?php

declare(strict_types=1);

namespace App\DTO\Request\Dog;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for creating a dog profile.
 */
final readonly class CreateDogRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name cannot be blank.')]
        #[Assert\Length(
            min: 1,
            max: 100,
            minMessage: 'Name must be at least {{ limit }} character long.',
            maxMessage: 'Name cannot be longer than {{ limit }} characters.'
        )]
        public string $name,

        #[Assert\NotBlank(message: 'Breed cannot be blank.')]
        #[Assert\Length(
            max: 100,
            maxMessage: 'Breed cannot be longer than {{ limit }} characters.'
        )]
        public string $breed,

        #[Assert\NotNull(message: 'Age in months is required.')]
        #[Assert\Type(type: 'integer', message: 'Age must be an integer.')]
        #[Assert\Range(
            min: 0,
            max: 300,
            notInRangeMessage: 'Age must be between {{ min }} and {{ max }} months.'
        )]
        public int $ageMonths,

        #[Assert\NotBlank(message: 'Gender cannot be blank.')]
        #[Assert\Choice(
            choices: ['male', 'female'],
            message: 'Gender must be either "male" or "female".'
        )]
        public string $gender,

        #[Assert\NotNull(message: 'Weight is required.')]
        #[Assert\Type(type: 'numeric', message: 'Weight must be a number.')]
        #[Assert\Range(
            min: 0.01,
            max: 200.00,
            notInRangeMessage: 'Weight must be between {{ min }} and {{ max }} kg.'
        )]
        public float $weightKg,

        #[Assert\NotBlank(message: 'Energy level cannot be blank.')]
        #[Assert\Choice(
            choices: ['very_low', 'low', 'medium', 'high', 'very_high'],
            message: 'Energy level must be one of: very_low, low, medium, high, very_high.'
        )]
        public string $energyLevel,
    ) {
    }
}
