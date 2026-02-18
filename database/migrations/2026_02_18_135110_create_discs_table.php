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
        Schema::create('discs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // lost or found
            $table->timestamp('occurred_at')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model_name')->nullable();
            $table->string('plastic_type')->nullable();
            $table->string('back_text')->nullable();
            $table->string('condition_estimate')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disc_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_type')->nullable(); // lost or found
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('disc_colors', function (Blueprint $table) {
            $table->foreignId('disc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained()->cascadeOnDelete();
            $table->primary(['disc_id', 'color_id']);
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lost_disc_id')->constrained('discs')->cascadeOnDelete();
            $table->foreignId('found_disc_id')->constrained('discs')->cascadeOnDelete();
            $table->float('match_score')->nullable();
            $table->string('status')->nullable(); // pending, confirmed, rejected
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('match_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content')->nullable();
            $table->timestamps();
        });

        Schema::create('confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->boolean('owner_confirmed')->default(false);
            $table->boolean('finder_confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmations');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('disc_colors');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('discs');
    }
};
