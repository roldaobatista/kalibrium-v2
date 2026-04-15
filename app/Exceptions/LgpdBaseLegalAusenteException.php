<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class LgpdBaseLegalAusenteException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Registre a base legal LGPD em Configuracoes > LGPD antes de capturar consentimentos');
    }
}
