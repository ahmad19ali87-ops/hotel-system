<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hotels')) {
            return;
        }

        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->unsignedTinyInteger('stars')->default(3);
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('province', 120)->nullable();
            $table->text('description')->nullable();
            $table->text('images')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};