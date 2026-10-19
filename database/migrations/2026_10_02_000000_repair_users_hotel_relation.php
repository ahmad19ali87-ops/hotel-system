<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'hotel_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
            });
        }

        $hotelIds = DB::table('hotels')->limit(2)->pluck('id');

        if ($hotelIds->count() === 1) {
            DB::table('users')
                ->where('role', 'admin')
                ->whereNull('hotel_id')
                ->update(['hotel_id' => $hotelIds->first()]);
        }
    }

    public function down(): void
    {
        // The original users migration already declares this column for new databases.
    }
};