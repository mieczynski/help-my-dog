<?php

declare(strict_types=1);

namespace App\Factory\Dog;

use App\Action\Command\Dog\CreateDogCommand;
use App\Entity\Dog;
use Symfony\Bundle\SecurityBundle\Security;

class DogFactory implements DogFactoryInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }
    public function createFromCommand(CreateDogCommand $command): Dog
    {
        return $this->createDog($command);
    }

    private function createDog(CreateDogCommand $command): Dog
    {
        $dog = new Dog();
        $dog->setUser($this->security->getUser());
        $dog->setName($command->name);
        $dog->setBreed($command->breed);
        $dog->setAgeMonths($command->ageMonths);
        $dog->setGender($command->gender);
        $dog->setWeightKg($command->weightKg);
        $dog->setEnergyLevel($command->energyLevel);

        return $dog;
    }
}
