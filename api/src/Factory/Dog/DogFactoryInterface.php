<?php

declare(strict_types=1);

namespace App\Factory\Dog;

use App\Action\Command\Dog\CreateDogCommand;
use App\Entity\Dog;

interface DogFactoryInterface
{
    public function createFromCommand(CreateDogCommand $command): Dog;
}
