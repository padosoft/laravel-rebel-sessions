<?php

declare(strict_types=1);

use Padosoft\Rebel\Core\Clock\FakeClock;
use Padosoft\Rebel\Core\Context\DeviceContext;
use Padosoft\Rebel\Core\Contracts\DeviceTrust;
use Psr\Clock\ClockInterface;

it('trusts a device, expires it, and untrusts it', function (): void {
    $clock = new FakeClock(new DateTimeImmutable('2026-01-01 10:00:00'));
    app()->instance(ClockInterface::class, $clock);

    $user = subjectUser();
    $device = new DeviceContext(fingerprintHash: 'fp-abc123');
    $trust = app(DeviceTrust::class);

    expect($trust->isTrusted($user, $device))->toBeFalse();

    $trust->trust($user, $device, 30);
    expect($trust->isTrusted($user, $device))->toBeTrue();

    // After the trust window the device is no longer trusted.
    $clock->advance(31 * 86400);
    expect($trust->isTrusted($user, $device))->toBeFalse();

    // Re-trust then explicitly untrust.
    $trust->trust($user, $device, 30);
    $trust->untrust($user, $device);
    expect($trust->isTrusted($user, $device))->toBeFalse();
});

it('never trusts a device without a fingerprint', function (): void {
    $user = subjectUser();
    $trust = app(DeviceTrust::class);

    $trust->trust($user, new DeviceContext, 30);

    expect($trust->isTrusted($user, new DeviceContext))->toBeFalse();
});
