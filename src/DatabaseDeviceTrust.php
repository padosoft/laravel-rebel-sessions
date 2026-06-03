<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Padosoft\Rebel\Core\Context\DeviceContext;
use Padosoft\Rebel\Core\Contracts\DeviceTrust;
use Padosoft\Rebel\Sessions\Models\RebelDevice;
use Psr\Clock\ClockInterface;

/**
 * Default {@see DeviceTrust}: remembers trusted devices by fingerprint hash, expiring
 * after the configured number of days.
 */
final class DatabaseDeviceTrust implements DeviceTrust
{
    public function __construct(private readonly ClockInterface $clock) {}

    public function isTrusted(Authenticatable $user, DeviceContext $device): bool
    {
        $fingerprint = $device->fingerprintHash;

        if ($fingerprint === null || $fingerprint === '') {
            return false;
        }

        $record = $this->find($user, $fingerprint);

        if ($record === null || ! $record->trusted) {
            return false;
        }

        return $record->trusted_until === null
            || $record->trusted_until > CarbonImmutable::instance($this->clock->now());
    }

    public function trust(Authenticatable $user, DeviceContext $device, int $days): void
    {
        $fingerprint = $device->fingerprintHash;

        if ($fingerprint === null || $fingerprint === '') {
            return;
        }

        $now = CarbonImmutable::instance($this->clock->now());

        // Atomic upsert keyed on (subject, fingerprint); BelongsToTenant scopes the match
        // and populates tenant_id on insert, so concurrent calls don't duplicate-key crash.
        RebelDevice::query()->updateOrCreate(
            [
                'subject_type' => $user::class,
                'subject_id' => $this->subjectId($user),
                'fingerprint_hash' => $fingerprint,
            ],
            [
                'trusted' => true,
                'trusted_until' => $now->addDays($days),
                'last_seen_at' => $now,
            ],
        );
    }

    public function untrust(Authenticatable $user, DeviceContext $device): void
    {
        $fingerprint = $device->fingerprintHash;

        if ($fingerprint === null || $fingerprint === '') {
            return;
        }

        $record = $this->find($user, $fingerprint);

        if ($record !== null) {
            $record->trusted = false;
            $record->trusted_until = null;
            $record->save();
        }
    }

    private function find(Authenticatable $user, string $fingerprint): ?RebelDevice
    {
        return RebelDevice::query()
            ->where('subject_type', $user::class)
            ->where('subject_id', $this->subjectId($user))
            ->where('fingerprint_hash', $fingerprint)
            ->first();
    }

    private function subjectId(Authenticatable $user): string
    {
        $id = $user->getAuthIdentifier();

        return is_scalar($id) ? (string) $id : '';
    }
}
