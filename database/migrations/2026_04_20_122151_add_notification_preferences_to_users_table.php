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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notify_disc_expiring')->default(true)->after('banned_reason');
            $table->boolean('email_notify_disc_expired')->default(true)->after('email_notify_disc_expiring');
            $table->boolean('email_notify_new_message')->default(true)->after('email_notify_disc_expired');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_notify_disc_expiring',
                'email_notify_disc_expired',
                'email_notify_new_message',
            ]);
        });
    }
};
