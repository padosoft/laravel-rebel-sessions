<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Padosoft\Rebel\Core\Concerns\BelongsToTenant;
use Padosoft\Rebel\Sessions\Enums\SessionStatus;
use Padosoft\Rebel\Sessions\Enums\SessionType;

/**
 * A subject's session or refresh token. Refresh tokens form a rotation chain via
 * `parent_id`; presenting an already-consumed refresh token is a reuse (theft) signal.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $subject_type
 * @property string $subject_id
 * @property SessionType $type
 * @property SessionStatus $status
 * @property string|null $parent_id
 * @property string|null $root_id
 * @property string|null $device_id
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $revoked_at
 */
final class RebelSession extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'rebel_sessions';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'type', 'status',
        'parent_id', 'root_id', 'device_id', 'expires_at', 'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SessionType::class,
            'status' => SessionStatus::class,
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
