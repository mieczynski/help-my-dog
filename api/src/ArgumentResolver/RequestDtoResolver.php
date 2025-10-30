<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Attribute\FromBody;
use App\Attribute\FromQuery;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class RequestDtoResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($argument)) {
            return [];
        }

        $dto = $this->deserializeDto($request, $argument);
        $this->validateDto($dto);

        return [$dto];
    }

    private function supports(ArgumentMetadata $argument): bool
    {
        $attributes = $argument->getAttributesOfType(FromBody::class);
        if (count($attributes) > 0) {
            return true;
        }

        $attributes = $argument->getAttributesOfType(FromQuery::class);

        return count($attributes) > 0;
    }

    private function deserializeDto(Request $request, ArgumentMetadata $argument): object
    {
        $type = $argument->getType();
        if (!$type) {
            throw new \InvalidArgumentException('Argument type cannot be null');
        }

        $fromBodyAttributes = $argument->getAttributesOfType(FromBody::class);
        if (count($fromBodyAttributes) > 0) {
            return $this->deserializeFromBody($request, $type);
        }

        return $this->deserializeFromQuery($request, $type);
    }

    private function deserializeFromBody(Request $request, string $type): object
    {
        $content = $request->getContent();

        return $this->serializer->deserialize($content, $type, 'json');
    }

    private function deserializeFromQuery(Request $request, string $type): object
    {
        $data = $request->query->all();

        return $this->serializer->denormalize($data, $type);
    }

    private function validateDto(object $dto): void
    {
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
