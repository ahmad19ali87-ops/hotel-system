<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('rooms', function (Blueprint $table) {
      
        // available, booked, cleaning, maintenance
        $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
        $table->boolean('needs_cleaning')->default(false);
        $table->text('maintenance_notes')->nullable();
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
