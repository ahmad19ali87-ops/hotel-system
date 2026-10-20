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
        // 1. Users table verification security columns
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'verification_code_sent_at')) {
                    $table->timestamp('verification_code_sent_at')->nullable()->after('verification_code');
                }
                if (!Schema::hasColumn('users', 'verification_attempts')) {
                    $table->unsignedInteger('verification_attempts')->default(0)->after('verification_code_sent_at');
                }
            });
        }

        // 2. Blocked IPs table
        if (!Schema::hasTable('blocked_ips')) {
            Schema::create('blocked_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->string('reason', 255)->nullable();
                $table->timestamp('blocked_until')->nullable()->index();
                $table->unsignedInteger('violations_count')->default(1);
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }

        // 3. Security Logs table
        if (!Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event_type', 100)->index(); // replay_attack, sql_injection, malicious_file, brute_force, fake_google_token, etc.
                $table->string('severity', 20)->default('warning'); // info, warning, critical
                $table->string('method', 10)->nullable();
                $table->string('url', 500)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('blocked_ips');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'verification_code_sent_at')) {
                    $table->dropColumn('verification_code_sent_at');
                }
                if (Schema::hasColumn('users', 'verification_attempts')) {
                    $table->dropColumn('verification_attempts');
                }
            });
        }
    }
};

