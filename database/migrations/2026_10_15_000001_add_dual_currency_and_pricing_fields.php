<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rooms') && !Schema::hasColumn('rooms', 'price_usd')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->decimal('price_usd', 12, 2)->nullable()->default(0)->after('price');
            });
        }

        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'currency')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('total');
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'currency')) {
                    $table->string('currency', 10)->default('SYP')->after('total_price');
                }
                if (!Schema::hasColumn('bookings', 'total_price_usd')) {
                    $table->decimal('total_price_usd', 12, 2)->nullable()->default(0)->after('currency');
                }
            });
        }

        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'currency')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('amount');
            });
        }

        if (Schema::hasTable('deletion_logs') && !Schema::hasColumn('deletion_logs', 'currency')) {
            Schema::table('deletion_logs', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'price_usd')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('price_usd');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'currency')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'total_price_usd')) {
                    $table->dropColumn('total_price_usd');
                }
                if (Schema::hasColumn('bookings', 'currency')) {
                    $table->dropColumn('currency');
                }
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'currency')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        if (Schema::hasTable('deletion_logs') && Schema::hasColumn('deletion_logs', 'currency')) {
            Schema::table('deletion_logs', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};

