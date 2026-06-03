<?php

declare(strict_types=1);

use Padosoft\Rebel\Core\Clock\FakeClock;
use Padosoft\Rebel\Core\Contracts\SessionRegistry;
use Padosoft\Rebel\Sessions\DatabaseSessionRegistry;
use Padosoft\Rebel\Sessions\Enums\SessionStatus;
use Padosoft\Rebel\Sessions\Enums\SessionType;
use Padosoft\Rebel\Sessions\Models\RebelSession;
use Padosoft\Rebel\Sessions\SessionManager;
use Psr\Clock\ClockInterface;

it('rotates a refresh token and consumes the old one', function (): void {
    $user = subjectUser();
    $manager = app(SessionManager::class);

    $first = $manager->start($user, SessionType::Refresh);
    $second = $manager->rotateRefresh($first->id, $user);

    expect($second)->not->toBeNull()
        ->and($second->parent_id)->toBe($first->id)
        ->and($second->status)->toBe(SessionStatus::Active)
        ->and(RebelSession::query()->findOrFail($first->id)->status)->toBe(SessionStatus::Consumed);
});

it('detects refresh-token reuse and burns the whole chain', function (): void {
    $user = subjectUser();
    $manager = app(SessionManager::class);

    $first = $manager->start($user, SessionType::Refresh);
    $manager->rotateRefresh($first->id, $user); // consumes $first, issues a child

    // Presenting the already-consumed token again is reuse → theft signal.
    expect($manager->isRefreshReused($first->id))->toBeTrue()
        ->and($manager->rotateRefresh($first->id, $user))->toBeNull()
        ->and(RebelSession::query()->where('status', SessionStatus::ReuseDetected->value)->count())->toBeGreaterThanOrEqual(2);
});

it('refuses to rotate another subject refresh token', function (): void {
    $owner = subjectUser(1);
    $attacker = subjectUser(2);
    $manager = app(SessionManager::class);

    $token = $manager->start($owner, SessionType::Refresh);

    expect($manager->rotateRefresh($token->id, $attacker))->toBeNull()
        // The owner's token must remain untouched.
        ->and(RebelSession::query()->findOrFail($token->id)->status)->toBe(SessionStatus::Active);
});

it('refuses to rotate an expired refresh token', function (): void {
    $clock = new FakeClock(new DateTimeImmutable('2026-01-01 10:00:00'));
    app()->instance(ClockInterface::class, $clock);

    $user = subjectUser();
    $manager = app(SessionManager::class);
    $token = $manager->start($user, SessionType::Refresh, ttlSeconds: 60);

    $clock->advance(120);

    expect($manager->rotateRefresh($token->id, $user))->toBeNull()
        ->and(RebelSession::query()->findOrFail($token->id)->status)->toBe(SessionStatus::Revoked);
});

it('burns session tokens too when reuse is detected', function (): void {
    $user = subjectUser();
    $manager = app(SessionManager::class);

    $session = $manager->start($user, SessionType::Session);
    $refresh = $manager->start($user, SessionType::Refresh);
    $manager->rotateRefresh($refresh->id, $user); // consume -> child

    $manager->rotateRefresh($refresh->id, $user); // reuse -> burn ALL subject tokens

    expect(RebelSession::query()->findOrFail($session->id)->status)->toBe(SessionStatus::ReuseDetected);
});

it('revokes all active sessions (logout everywhere)', function (): void {
    $user = subjectUser();
    $manager = app(SessionManager::class);

    $manager->start($user, SessionType::Session);
    $manager->start($user, SessionType::Refresh);

    expect($manager->revokeAll($user))->toBe(2)
        ->and(RebelSession::query()->where('status', SessionStatus::Active->value)->count())->toBe(0);
});

it('binds the core SessionRegistry contract to the database implementation', function (): void {
    $registry = app(SessionRegistry::class);

    expect($registry)->toBeInstanceOf(DatabaseSessionRegistry::class);

    $user = subjectUser(7);
    app(SessionManager::class)->start($user, SessionType::Session);

    expect($registry->revokeAll($user))->toBe(1);
});
