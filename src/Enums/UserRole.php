<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::CLIENT => 'Customer',
            self::ADMIN => 'Administrator',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}