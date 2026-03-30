<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confirmations', function (Blueprint $table) {
            $table->boolean('owner_handed_over')->default(false)->after('finder_confirmed');
            $table->boolean('finder_handed_over')->default(false)->after('owner_handed_over');
            $table->timestamp('handed_over_at')->nullable()->after('finder_handed_over');
        });
    }

    public function down(): void
    {
        Schema::table('confirmations', function (Blueprint $table) {
            $table->dropColumn(['owner_handed_over', 'finder_handed_over', 'handed_over_at']);
        });
    }
};
