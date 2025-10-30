<?php

declare(strict_types=1);

namespace App\Controller;

use App\Action\Command\Dog\CreateDogCommand;
use App\Attribute\FromBody;
use App\DTO\Request\Dog\CreateDogRequestDTO;
use App\DTO\Response\Dog\DogResponseDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for Dog resource endpoints.
 */
#[Route('/api')]
class DogController extends AbstractController
{
    use HandleTrait;

    /**
     * Create a new dog profile.
     */
    #[Route('/dogs', name: 'api_dog_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        #[FromBody] CreateDogRequestDTO $requestDTO,
    ): JsonResponse {
        $command = new CreateDogCommand(
            name: $requestDTO->name,
            breed: $requestDTO->breed,
            ageMonths: $requestDTO->ageMonths,
            gender: $requestDTO->gender,
            weightKg: (string) $requestDTO->weightKg,
            energyLevel: $requestDTO->energyLevel,
        );

        $dog = $this->handle($command);

        $responseDTO = DogResponseDTO::fromEntity($dog);

        return new JsonResponse($responseDTO, Response::HTTP_CREATED);
    }
}
