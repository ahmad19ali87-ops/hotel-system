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
    Schema::create('maintenance', function (Blueprint $table) {
    $table->id();
     $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
     $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
     $table->string('issue_type');
     $table->string('priority')->default('normal');
     $table->text('notes')->nullable();
     $table->string('status')->default('open');
     $table->dateTime('opened_at')->nullable();
     $table->dateTime('closed_at')->nullable();
    $table->timestamps();
});

}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance');
    }
};
