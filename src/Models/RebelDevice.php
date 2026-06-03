<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Sessions\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Padosoft\Rebel\Core\Concerns\BelongsToTenant;

/**
 * A remembered device for a subject. A trusted device reduces step-up friction until
 * it expires (`trusted_until`) or is untrusted.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $fingerprint_hash
 * @property bool $trusted
 * @property CarbonImmutable|null $trusted_until
 * @property CarbonImmutable|null $last_seen_at
 */
final class RebelDevice extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'rebel_devices';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'fingerprint_hash',
        'trusted', 'trusted_until', 'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trusted' => 'boolean',
            'trusted_until' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
        ];
    }
}
