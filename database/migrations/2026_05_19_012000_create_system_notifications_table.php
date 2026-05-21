<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 60);
            $table->string('severity', 30)->default('INFO');
            $table->string('status', 30)->default('UNREAD');
            $table->string('title', 180);
            $table->text('body');
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('fingerprint', 160);
            $table->json('payload')->nullable();
            $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['fingerprint', 'status']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['company_id', 'severity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
