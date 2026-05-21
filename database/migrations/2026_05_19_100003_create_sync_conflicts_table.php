<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sync_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offline_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('conflict_type', 30); // DRAW_CLOSED, LIMIT_EXCEEDED, DUPLICATE, VALIDATION_ERROR
            $table->string('status', 20)->default('PENDING'); // PENDING, RESOLVED_ACCEPT, RESOLVED_REJECT
            $table->json('ticket_data');
            $table->text('conflict_reason')->nullable();
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['offline_session_id', 'status']);
            $table->index(['sync_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
