<?php

declare(strict_types=1);

namespace App\Action\Command\Dog;

final readonly class CreateDogCommand
{
    public function __construct(
        public string $name,
        public string $breed,
        public int $ageMonths,
        public string $gender,
        public string $weightKg,
        public string $energyLevel,
    ) {
    }
}
