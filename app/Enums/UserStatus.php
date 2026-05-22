<?php
namespace App\Enums;


enum UserStatus: string{
    case Active = 'active';
    case Blocked = 'blocked';
    case Unavailable = 'unavailable';
    case Closed = 'closed';
}