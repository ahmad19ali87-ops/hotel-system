<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $accountIndexes = collect(Schema::getIndexes('accounts'))->pluck('name')->all();
        if (in_array('accounts_invoice_id_unique', $accountIndexes, true)) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropUnique('accounts_invoice_id_unique');
            });
        }

        $roomIndexes = collect(Schema::getIndexes('rooms'))->pluck('name')->all();
        if (in_array('rooms_room_number_unique', $roomIndexes, true)) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropUnique('rooms_room_number_unique');
            });
        }
        if (! in_array('rooms_hotel_room_number_unique', $roomIndexes, true)) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->unique(['hotel_id', 'room_number'], 'rooms_hotel_room_number_unique');
            });
        }
    }

    public function down(): void
    {
        // Rollback must not restore global room numbering or the nullable unique ledger constraint.
    }
};