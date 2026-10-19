<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'bookings',
        'guests',
        'invoices',
        'maintenance',
        'cleaning_logs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'hotel_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
                });
            }
        }

        $hotelIds = DB::table('hotels')->limit(2)->pluck('id');

        if ($hotelIds->count() === 1) {
            foreach ($this->tables as $tableName) {
                if (Schema::hasTable($tableName)) {
                    DB::table($tableName)
                        ->whereNull('hotel_id')
                        ->update(['hotel_id' => $hotelIds->first()]);
                }
            }
        }
    }

    public function down(): void
    {
        // These relations are part of the original hotel schema and must remain on rollback.
    }
};