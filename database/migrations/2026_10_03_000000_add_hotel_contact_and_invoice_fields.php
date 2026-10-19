<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hotels', 'email')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->string('email')->nullable();
            });
        }

        if (! Schema::hasColumn('hotels', 'phones')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->text('phones')->nullable();
            });
        }

        if (! Schema::hasColumn('hotels', 'deleted_at')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('invoices', 'service_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('service_amount', 10, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('invoices', 'service_description')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->text('service_description')->nullable();
            });
        }

        if (! Schema::hasColumn('invoices', 'pdf_path')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('pdf_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Contact details, invoice services, and PDF references are retained for data safety.
    }
};