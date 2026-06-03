<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions;

use Illuminate\Contracts\Auth\Authenticatable;
use Padosoft\Rebel\Core\Contracts\SessionRegistry;

/**
 * Default {@see SessionRegistry} (the contract OTP/step-up depend on), backed by the
 * {@see SessionManager}.
 */
final class DatabaseSessionRegistry implements SessionRegistry
{
    public function __construct(private readonly SessionManager $manager) {}

    public function revokeAll(Authenticatable $user): int
    {
        return $this->manager->revokeAll($user);
    }

    public function isRefreshReused(string $refreshTokenId): bool
    {
        return $this->manager->isRefreshReused($refreshTokenId);
    }
}
