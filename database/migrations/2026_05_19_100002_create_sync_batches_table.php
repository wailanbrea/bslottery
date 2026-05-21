<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('offline_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('PENDING'); // PENDING, PROCESSING, COMPLETED, PARTIAL, FAILED
            $table->timestamp('submitted_at');
            $table->timestamp('processed_at')->nullable();
            $table->integer('total_tickets')->default(0);
            $table->integer('accepted_tickets')->default(0);
            $table->integer('rejected_tickets')->default(0);
            $table->string('payload_hash', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['offline_session_id']);
            $table->index(['device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batches');
    }
};
