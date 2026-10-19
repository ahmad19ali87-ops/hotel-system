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
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');

    $table->string('status')->default('confirmed')->change();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
