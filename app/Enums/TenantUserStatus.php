<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantUserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Invited = 'invited';
}
