<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'invoice_number')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('invoice_number')->nullable();
            });
        }

        if (! Schema::hasColumn('room_types', 'hotel_id')) {
            Schema::table('room_types', function (Blueprint $table) {
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('bookings', 'created_by')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('cleaning_logs') && ! Schema::hasColumn('cleaning_logs', 'hotel_id')) {
            Schema::table('cleaning_logs', function (Blueprint $table) {
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('accounts', 'invoice_id')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            });
        }

        $hotelIds = DB::table('hotels')->limit(2)->pluck('id');
        if ($hotelIds->count() === 1) {
            DB::table('room_types')->whereNull('hotel_id')->update(['hotel_id' => $hotelIds->first()]);
            if (Schema::hasTable('cleaning_logs')) {
                DB::table('cleaning_logs')->whereNull('hotel_id')->update(['hotel_id' => $hotelIds->first()]);
            }
        }

        if (! Schema::hasTable('hotel_reviews')) {
            Schema::create('hotel_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
                $table->string('guest_name', 120)->nullable();
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->index(['hotel_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_reviews');
    }
};