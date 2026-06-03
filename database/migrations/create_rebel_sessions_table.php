<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rebel_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('tenant_id')->nullable();
            $table->string('subject_type');
            $table->string('subject_id');

            $table->string('type');                 // session | refresh
            $table->string('status')->default('active'); // active | revoked | consumed | reuse_detected
            $table->uuid('parent_id')->nullable();  // immediate predecessor in the rotation chain
            $table->uuid('root_id')->nullable();    // root of the rotation chain (locked to serialize rotations)
            $table->string('device_id')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'status']);
            $table->index('parent_id');
            $table->index('root_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rebel_sessions');
    }
};
