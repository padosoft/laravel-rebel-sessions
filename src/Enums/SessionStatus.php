<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions\Enums;

enum SessionStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Consumed = 'consumed';
    case ReuseDetected = 'reuse_detected';
}
