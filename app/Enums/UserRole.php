<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Guide = 'guide';
    case Admin = 'admin';
}
