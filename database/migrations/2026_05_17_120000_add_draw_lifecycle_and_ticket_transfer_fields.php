<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draws', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('closed_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable()->after('cancelled_by');
            $table->string('ticket_resolution_policy', 40)->nullable()->after('cancel_reason');
            $table->foreignId('transferred_to_draw_id')->nullable()->after('ticket_resolution_policy')->constrained('draws')->nullOnDelete();

            $table->index(['company_id', 'status', 'cancelled_at']);
            $table->index('transferred_to_draw_id');
        });

        Schema::table('ticket_details', function (Blueprint $table): void {
            $table->foreignId('transferred_from_draw_id')->nullable()->after('draw_id')->constrained('draws')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable()->after('transferred_from_draw_id');
            $table->foreignId('transferred_by')->nullable()->after('transferred_at')->constrained('users')->nullOnDelete();

            $table->index('transferred_from_draw_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_details', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transferred_by');
            $table->dropColumn('transferred_at');
            $table->dropConstrainedForeignId('transferred_from_draw_id');
        });

        Schema::table('draws', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'status', 'cancelled_at']);
            $table->dropIndex(['transferred_to_draw_id']);
            $table->dropConstrainedForeignId('transferred_to_draw_id');
            $table->dropColumn('ticket_resolution_policy');
            $table->dropColumn('cancel_reason');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn('cancelled_at');
        });
    }
};
