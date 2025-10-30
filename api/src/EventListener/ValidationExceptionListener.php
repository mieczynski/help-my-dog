<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ValidationException) {
            return;
        }

        $violations = $this->formatViolations($exception);

        $response = new JsonResponse([
            'error' => 'validation_failed',
            'message' => $exception->getMessage(),
            'violations' => $violations,
        ], Response::HTTP_BAD_REQUEST);

        $event->setResponse($response);
    }

    private function formatViolations(ValidationException $exception): array
    {
        $violations = [];
        foreach ($exception->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $violations;
    }
}
