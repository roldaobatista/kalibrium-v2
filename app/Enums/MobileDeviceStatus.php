<?php

declare(strict_types=1);

namespace App\Enums;

enum MobileDeviceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Revoked = 'revoked';
}
