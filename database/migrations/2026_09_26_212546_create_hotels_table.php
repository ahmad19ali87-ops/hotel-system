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
    if (! Schema::hasTable('hotels')) {
        Schema::create('hotels', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique(); // رابط الفندق مثل /hilton
        $table->string('logo')->nullable();
        $table->integer('stars')->default(3);
        $table->string('phone')->nullable();
        $table->string('address')->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
        });
    }
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
