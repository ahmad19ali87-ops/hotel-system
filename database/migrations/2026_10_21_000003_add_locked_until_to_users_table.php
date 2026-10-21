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
        // 1. Account lockout column for anti-brute force
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'locked_until')) {
                    $table->timestamp('locked_until')->nullable()->index()->after('password');
                }
            });
        }

        // 2. Action taken column for Threat Auto-Response Engine audit trail
        if (Schema::hasTable('security_logs')) {
            Schema::table('security_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('security_logs', 'action_taken')) {
                    $table->string('action_taken', 100)->nullable()->index()->after('severity');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'locked_until')) {
                    $table->dropColumn('locked_until');
                }
            });
        }

        if (Schema::hasTable('security_logs')) {
            Schema::table('security_logs', function (Blueprint $table) {
                if (Schema::hasColumn('security_logs', 'action_taken')) {
                    $table->dropColumn('action_taken');
                }
            });
        }
    }
};

