<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_id')->constrained('users')->cascadeOnDelete();

            // Null = support chat. Non-null = specific match chat thread.
            $table->foreignId('match_id')->nullable()->constrained('matches')->cascadeOnDelete();

            $table->string('reason'); // harassment, spam, scam, other
            $table->text('details')->nullable();

            // Snapshot (helps admin even if messages get deleted later)
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->index(['reported_id', 'created_at']);
            $table->index(['match_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_reports');
    }
};
