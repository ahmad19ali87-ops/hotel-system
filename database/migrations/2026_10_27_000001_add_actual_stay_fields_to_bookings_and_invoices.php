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
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'actual_check_in_at')) {
                    $table->dateTime('actual_check_in_at')->nullable()->after('check_out');
                }
                if (!Schema::hasColumn('bookings', 'actual_check_out_at')) {
                    $table->dateTime('actual_check_out_at')->nullable()->after('actual_check_in_at');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'nights')) {
                    $table->integer('nights')->nullable()->after('room_id');
                }
                if (!Schema::hasColumn('invoices', 'nightly_rate')) {
                    $table->decimal('nightly_rate', 10, 2)->nullable()->after('nights');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'actual_check_out_at')) {
                    $table->dropColumn('actual_check_out_at');
                }
                if (Schema::hasColumn('bookings', 'actual_check_in_at')) {
                    $table->dropColumn('actual_check_in_at');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'nightly_rate')) {
                    $table->dropColumn('nightly_rate');
                }
                if (Schema::hasColumn('invoices', 'nights')) {
                    $table->dropColumn('nights');
                }
            });
        }
    }
};

