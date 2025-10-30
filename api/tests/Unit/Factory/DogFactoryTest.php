<?php

declare(strict_types=1);

namespace App\Tests\Unit\Factory;

use App\Action\Command\Dog\CreateDogCommand;
use App\Entity\Dog;
use App\Entity\User;
use App\Factory\Dog\DogFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class DogFactoryTest extends TestCase
{
    public function testCreateFromCommandSuccessfully(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPasswordHash('hashed_password');

        $security = $this->createMock(Security::class);

        $factory = new DogFactory($security);

        $command = new CreateDogCommand(
            name: 'Rex',
            breed: 'German Shepherd',
            ageMonths: 24,
            gender: 'male',
            weightKg: '35.50',
            energyLevel: 'high',
        );

        // Act
        $dog = $factory->createFromCommand($command);

        // Assert
        $this->assertInstanceOf(Dog::class, $dog);
        $this->assertSame('Rex', $dog->getName());
        $this->assertSame('German Shepherd', $dog->getBreed());
        $this->assertSame(24, $dog->getAgeMonths());
        $this->assertSame('male', $dog->getGender());
        $this->assertSame('35.50', $dog->getWeightKg());
        $this->assertSame('high', $dog->getEnergyLevel());
        $this->assertSame($user, $dog->getUser());
    }

    public function testCreateFromCommandThrowsExceptionWhenUserNotFound(): void
    {
        // Arrange
        $security = $this->createMock(Security::class);

        $factory = new DogFactory($security);

        $command = new CreateDogCommand(
            name: 'Rex',
            breed: 'German Shepherd',
            ageMonths: 24,
            gender: 'male',
            weightKg: '35.50',
            energyLevel: 'high',
        );

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User with ID non-existent-user-id not found');

        // Act
        $factory->createFromCommand($command);
    }
}
