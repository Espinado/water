<?php

namespace App\Enums;

enum UserRole: string
{
    case Manager = 'manager';
    case Resident = 'resident';

    public function isManager(): bool
    {
        return $this === self::Manager;
    }
}
