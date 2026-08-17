<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: int
{
    case USER = 1;
    case MEMBER = 2;
    case ADMIN = 3;
}
