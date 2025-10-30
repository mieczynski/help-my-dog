<?php

declare(strict_types=1);

namespace App\Exception;

final class UserNotFoundException extends \RuntimeException
{
    public static function withId(string $userId): self
    {
        return new self(sprintf('User with ID %s not found', $userId));
    }
}
