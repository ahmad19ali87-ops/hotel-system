<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'tax_name')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('tax_name')->nullable();
            });
        }

        if (! Schema::hasColumn('invoices', 'tax_rate')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('tax_rate', 5, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        // Tax labels and rates are retained to preserve issued invoices.
    }
};