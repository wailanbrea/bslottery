<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_states', function (Blueprint $table): void {
            $table->id();
            $table->string('project_code', 80);
            $table->string('license_key', 120)->nullable();
            $table->string('device_fingerprint', 120)->unique();
            $table->string('device_name', 150);
            $table->string('device_type', 40)->default('web');
            $table->string('client_location_code', 100);
            $table->string('domain', 180)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('status', 40)->default('unactivated');
            $table->string('reason_code', 80)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('last_validation_success')->default(false);
            $table->timestamp('last_validation_at')->nullable();
            $table->timestamp('last_server_time')->nullable();
            $table->timestamp('last_seen_system_time')->nullable();
            $table->timestamp('offline_grace_expires_at')->nullable();
            $table->unsignedInteger('offline_launch_count')->default(0);
            $table->unsignedInteger('offline_operation_count')->default(0);
            $table->unsignedInteger('offline_sales_count')->default(0);
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->json('metadata')->nullable();
            $table->json('client')->nullable();
            $table->json('location')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['project_code', 'status']);
            $table->index(['license_key']);
            $table->index(['is_active', 'last_validation_success']);
            $table->index('expires_at');
        });

        Schema::create('license_validation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('license_state_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('project_code', 80);
            $table->string('license_key', 120)->nullable();
            $table->string('reason_code', 80)->nullable();
            $table->boolean('success')->default(false);
            $table->boolean('valid')->default(false);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('message')->nullable();
            $table->json('response_snapshot')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['license_state_id', 'created_at']);
            $table->index(['project_code', 'created_at']);
            $table->index(['reason_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_validation_logs');
        Schema::dropIfExists('license_states');
    }
};
