<?php

declare(strict_types=1);

namespace App\Action\Command\Dog;

use App\Entity\Dog;
use App\Factory\Dog\DogFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateDogCommandHandler
{
    public function __construct(
        private readonly DogFactoryInterface $dogFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(CreateDogCommand $command): Dog
    {
        $dog = $this->dogFactory->createFromCommand($command);

        $this->entityManager->persist($dog);
        $this->entityManager->flush();

        return $dog;
    }
}
