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
        Schema::table('discs', function (Blueprint $table) {
            $table->string('back_name')->nullable()->after('back_text');
            $table->string('back_number')->nullable()->after('back_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discs', function (Blueprint $table) {
            $table->dropColumn(['back_name', 'back_number']);
        });
    }
};
