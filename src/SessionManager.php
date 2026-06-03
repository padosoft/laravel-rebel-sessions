<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Padosoft\Rebel\Sessions\Enums\SessionStatus;
use Padosoft\Rebel\Sessions\Enums\SessionType;
use Padosoft\Rebel\Sessions\Models\RebelSession;
use Psr\Clock\ClockInterface;

/**
 * Tracks sessions and refresh tokens and implements refresh-token rotation with
 * reuse detection. Each refresh belongs to a chain identified by `root_id`; a rotation
 * consumes the old token and issues a child. Presenting an already-consumed/revoked
 * refresh — or one belonging to another subject, or an expired one — is rejected, and a
 * reuse signal burns ALL of the subject's tokens (theft response).
 *
 * Concurrency: every rotation locks the chain ROOT row first, so two rotations of the
 * same chain serialize and a sibling cannot escape a chain burn.
 */
final class SessionManager
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly DatabaseManager $db,
    ) {}

    public function start(Authenticatable $user, SessionType $type, ?string $deviceId = null, ?string $parentId = null, ?int $ttlSeconds = null, ?string $rootId = null): RebelSession
    {
        $now = CarbonImmutable::instance($this->clock->now());
        $id = (string) Str::uuid();

        $session = new RebelSession;
        $session->id = $id;
        $session->fill([
            'subject_type' => $user::class,
            'subject_id' => $this->subjectId($user),
            'type' => $type,
            'status' => SessionStatus::Active,
            'parent_id' => $parentId,
            'root_id' => $rootId ?? $id, // a root token is its own chain root
            'device_id' => $deviceId,
            'expires_at' => $ttlSeconds !== null ? $now->addSeconds($ttlSeconds) : null,
        ]);
        $session->save();

        return $session;
    }

    /**
     * Rotate a refresh token: consume it and return a fresh child. Returns null when the
     * token is unknown, not the caller's, expired, or being reused (reuse burns the chain).
     */
    public function rotateRefresh(string $refreshId, Authenticatable $user, ?int $ttlSeconds = null): ?RebelSession
    {
        return $this->db->connection()->transaction(function () use ($refreshId, $user, $ttlSeconds): ?RebelSession {
            $old = RebelSession::query()
                ->whereKey($refreshId)
                ->where('type', SessionType::Refresh->value)
                ->where('subject_type', $user::class)         // ownership: never rotate another subject's token
                ->where('subject_id', $this->subjectId($user))
                ->lockForUpdate()
                ->first();

            if ($old === null) {
                return null;
            }

            // Serialize all rotations of this chain on its root row.
            if ($old->root_id !== null && $old->root_id !== $old->id) {
                RebelSession::query()->whereKey($old->root_id)->lockForUpdate()->first();
                $old->refresh();
            }

            if ($old->status !== SessionStatus::Active) {
                // Reuse of a non-active refresh ⇒ token theft: burn ALL the subject's tokens.
                $this->burnSubjectTokens($old);

                return null;
            }

            $now = CarbonImmutable::instance($this->clock->now());
            if ($old->expires_at !== null && $old->expires_at <= $now) {
                $old->status = SessionStatus::Revoked;
                $old->revoked_at = $now;
                $old->save();

                return null;
            }

            $old->status = SessionStatus::Consumed;
            $old->save();

            return $this->start($user, SessionType::Refresh, $old->device_id, $old->id, $ttlSeconds, $old->root_id);
        });
    }

    /** Revoke every active session/refresh of the subject. Returns how many were revoked. */
    public function revokeAll(Authenticatable $user): int
    {
        return RebelSession::query()
            ->where('subject_type', $user::class)
            ->where('subject_id', $this->subjectId($user))
            ->where('status', SessionStatus::Active->value)
            ->update([
                'status' => SessionStatus::Revoked->value,
                'revoked_at' => $this->now(),
            ]);
    }

    /** Has this refresh token already been consumed/burned (i.e. reuse)? */
    public function isRefreshReused(string $refreshId): bool
    {
        $session = RebelSession::query()->whereKey($refreshId)->first();

        return $session !== null
            && in_array($session->status, [SessionStatus::Consumed, SessionStatus::ReuseDetected], true);
    }

    /** On theft: burn every still-live token of the subject (sessions AND refresh tokens). */
    private function burnSubjectTokens(RebelSession $node): void
    {
        RebelSession::query()
            ->where('subject_type', $node->subject_type)
            ->where('subject_id', $node->subject_id)
            ->whereIn('status', [SessionStatus::Active->value, SessionStatus::Consumed->value])
            ->update([
                'status' => SessionStatus::ReuseDetected->value,
                'revoked_at' => $this->now(),
            ]);
    }

    private function now(): string
    {
        return CarbonImmutable::instance($this->clock->now())->format('Y-m-d H:i:s');
    }

    private function subjectId(Authenticatable $user): string
    {
        $id = $user->getAuthIdentifier();

        return is_scalar($id) ? (string) $id : '';
    }
}
