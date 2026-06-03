<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rebel_devices', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('tenant_id')->nullable();
            $table->string('subject_type');
            $table->string('subject_id');

            $table->string('fingerprint_hash'); // already-hashed device fingerprint
            $table->boolean('trusted')->default(false);
            $table->timestamp('trusted_until')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'subject_type', 'subject_id', 'fingerprint_hash'], 'rebel_device_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rebel_devices');
    }
};
