<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('external_code', 100)->nullable()->index();
            $table->string('name', 150);
            $table->string('legal_name', 200)->nullable();
            $table->string('rnc', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->string('timezone', 80)->default('America/Santo_Domingo');
            $table->char('currency', 3)->default('DOP');
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('external_code', 100)->nullable()->index();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('manager_name', 150)->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->boolean('can_sell_online')->default(true);
            $table->boolean('can_sell_offline')->default(false);
            $table->unsignedInteger('offline_max_minutes')->default(120);
            $table->decimal('offline_total_limit', 14, 2)->default(0);
            $table->boolean('cash_control_enabled')->default(true);
            $table->boolean('accounting_enabled')->default(true);
            $table->boolean('payroll_enabled')->default(true);
            $table->unsignedBigInteger('default_printer_id')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'code']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->integer('level')->default(0);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index('status');
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('module', 100);
            $table->string('action', 100);
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name', 150);
            $table->string('document_number', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('position', 100)->default('Staff');
            $table->string('salary_type', 40)->default('FIXED');
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->string('status', 30)->default('ACTIVE');
            $table->date('hired_at')->nullable();
            $table->date('terminated_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('username', 80);
            $table->string('email', 150)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('ACTIVE');
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['company_id', 'username']);
            $table->unique(['company_id', 'email']);
            $table->index(['company_id', 'branch_id']);
            $table->index('role_id');
            $table->index('status');
            $table->index('username');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('device_type', 40);
            $table->string('platform', 100)->nullable();
            $table->string('device_fingerprint');
            $table->string('app_version', 50)->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index('device_fingerprint');
            $table->index('status');
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module', 100);
            $table->string('action', 100);
            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 80)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'branch_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'action']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('devices');
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('users');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
