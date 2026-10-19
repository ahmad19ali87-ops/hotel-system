<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hotels', 'province')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->string('province', 120)->nullable();
            });
        }

        if (! Schema::hasColumn('hotels', 'images')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->text('images')->nullable();
            });
        }

        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'invoice_number')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('invoice_number')->nullable();
            });
        }
    }

    public function down(): void
    {
        // These columns are required by the current hotel and invoice models.
    }
};