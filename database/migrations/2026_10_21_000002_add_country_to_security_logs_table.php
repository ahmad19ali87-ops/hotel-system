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
        if (Schema::hasTable('security_logs')) {
            Schema::table('security_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('security_logs', 'country_code')) {
                    $table->string('country_code', 10)->nullable()->index()->after('ip_address');
                }
                if (!Schema::hasColumn('security_logs', 'country_name')) {
                    $table->string('country_name', 100)->nullable()->after('country_code');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('security_logs')) {
            Schema::table('security_logs', function (Blueprint $table) {
                if (Schema::hasColumn('security_logs', 'country_code')) {
                    $table->dropColumn('country_code');
                }
                if (Schema::hasColumn('security_logs', 'country_name')) {
                    $table->dropColumn('country_name');
                }
            });
        }
    }
};
