<?php

declare(strict_types=1);

namespace App\Tests\Unit\Handler\Dog;

use App\Action\Command\Dog\CreateDogCommand;
use App\Action\Command\Dog\CreateDogCommandHandler;
use App\Entity\Dog;
use App\Factory\Dog\DogFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateDogCommandHandlerTest extends TestCase
{
    public function testHandlerCreatesAndPersistsDog(): void
    {
        // Arrange
        $dog = new Dog();
        $dog->setName('Rex');

        $dogFactory = $this->createMock(DogFactoryInterface::class);
        $dogFactory->method('createFromCommand')->willReturn($dog);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($dog);
        $entityManager->expects($this->once())->method('flush');

        $handler = new CreateDogCommandHandler($dogFactory, $entityManager);

        $command = new CreateDogCommand(
            userId: 'user-uuid',
            name: 'Rex',
            breed: 'German Shepherd',
            ageMonths: 24,
            gender: 'male',
            weightKg: '35.50',
            energyLevel: 'high',
        );

        // Act
        $result = $handler->__invoke($command);

        // Assert
        $this->assertSame($dog, $result);
    }
}
