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
        Schema::table('hotels', function (Blueprint $table) {
            if (!Schema::hasColumn('hotels', 'google_maps_url')) {
                $table->text('google_maps_url')->nullable()->after('address');
            }
        });

        Schema::table('hotel_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_reviews', 'hotel_reply')) {
                $table->text('hotel_reply')->nullable()->after('comment');
                $table->timestamp('replied_at')->nullable()->after('hotel_reply');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            if (Schema::hasColumn('hotels', 'google_maps_url')) {
                $table->dropColumn('google_maps_url');
            }
        });

        Schema::table('hotel_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_reviews', 'hotel_reply')) {
                $table->dropColumn(['hotel_reply', 'replied_at']);
            }
        });
    }
};

