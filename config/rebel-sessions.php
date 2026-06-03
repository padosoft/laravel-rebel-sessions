<?php

declare(strict_types=1);

return [

    // Default number of days a remembered device stays trusted (callers may override).
    'device_trust_days' => (int) env('REBEL_SESSIONS_DEVICE_TRUST_DAYS', 30),

];
