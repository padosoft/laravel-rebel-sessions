<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions\Enums;

enum SessionType: string
{
    case Session = 'session';
    case Refresh = 'refresh';
}
