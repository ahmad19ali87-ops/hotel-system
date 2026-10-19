<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts') && !Schema::hasColumn('accounts', 'currency')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->string('currency', 10)->default('SYP')->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'currency')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};

