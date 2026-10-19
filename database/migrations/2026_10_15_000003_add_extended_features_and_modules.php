<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hotel customizations (Tagline and Amenities)
        if (Schema::hasTable('hotels')) {
            Schema::table('hotels', function (Blueprint $table) {
                if (!Schema::hasColumn('hotels', 'tagline')) {
                    $table->string('tagline', 255)->nullable()->after('description');
                }
                if (!Schema::hasColumn('hotels', 'amenities')) {
                    $table->text('amenities')->nullable()->after('tagline');
                }
            });
        }

        // 2. User profile fields (Avatar, Phone, Bio, Job Title)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'avatar')) {
                    $table->string('avatar', 255)->nullable()->after('email');
                }
                if (!Schema::hasColumn('users', 'phone')) {
                    $table->string('phone', 40)->nullable()->after('avatar');
                }
                if (!Schema::hasColumn('users', 'bio')) {
                    $table->text('bio')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('users', 'job_title')) {
                    $table->string('job_title', 100)->nullable()->after('bio');
                }
            });
        }

        // 3. Guest extended identity, document and attachments
        if (Schema::hasTable('guests')) {
            Schema::table('guests', function (Blueprint $table) {
                if (!Schema::hasColumn('guests', 'first_name')) {
                    $table->string('first_name', 100)->nullable()->after('name');
                }
                if (!Schema::hasColumn('guests', 'father_name')) {
                    $table->string('father_name', 100)->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('guests', 'last_name')) {
                    $table->string('last_name', 100)->nullable()->after('father_name');
                }
                if (!Schema::hasColumn('guests', 'nationality')) {
                    $table->string('nationality', 100)->nullable()->default('سوري')->after('last_name');
                }
                if (!Schema::hasColumn('guests', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('nationality');
                }
                if (!Schema::hasColumn('guests', 'id_type')) {
                    $table->string('id_type', 50)->nullable()->default('national_id')->after('birth_date');
                }
                if (!Schema::hasColumn('guests', 'id_number')) {
                    $table->string('id_number', 100)->nullable()->after('id_type');
                }
                if (!Schema::hasColumn('guests', 'attachments')) {
                    $table->text('attachments')->nullable()->after('notes');
                }
            });
        }

        // 4. Destinations table for homepage customizer
        if (!Schema::hasTable('destinations')) {
            Schema::create('destinations', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->string('subtitle', 255)->nullable();
                $table->string('image', 255)->nullable();
                $table->string('link', 255)->nullable();
                $table->string('icon', 50)->nullable()->default('🏛️');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default 4 destinations
            DB::table('destinations')->insert([
                [
                    'title' => 'مدينة دمشق',
                    'subtitle' => 'عراقة الشرق وياسمين الشام القديمة والأسواق التاريخية',
                    'image' => null,
                    'link' => '/hotels?province=' . urlencode('دمشق'),
                    'icon' => '🏛️',
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'مدينة حلب',
                    'subtitle' => 'الشهباء وحصون التاريخ وقلعة حلب الشامخة',
                    'image' => null,
                    'link' => '/hotels?province=' . urlencode('حلب'),
                    'icon' => '🏰',
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'الساحل السوري',
                    'subtitle' => 'شواطئ اللاذقية وطرطوس الساحرة والمنتجعات البحرية',
                    'image' => null,
                    'link' => '/hotels?province=' . urlencode('اللاذقية'),
                    'icon' => '🌊',
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'title' => 'مصايف ريف دمشق',
                    'subtitle' => 'بلودان وصيدنايا ومعلولا وجبال القلمون الباردة',
                    'image' => null,
                    'link' => '/hotels?province=' . urlencode('ريف دمشق'),
                    'icon' => '🌲',
                    'sort_order' => 4,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('destinations')) {
            Schema::dropIfExists('destinations');
        }

        if (Schema::hasTable('hotels')) {
            Schema::table('hotels', function (Blueprint $table) {
                if (Schema::hasColumn('hotels', 'tagline')) $table->dropColumn('tagline');
                if (Schema::hasColumn('hotels', 'amenities')) $table->dropColumn('amenities');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'avatar')) $table->dropColumn('avatar');
                if (Schema::hasColumn('users', 'phone')) $table->dropColumn('phone');
                if (Schema::hasColumn('users', 'bio')) $table->dropColumn('bio');
                if (Schema::hasColumn('users', 'job_title')) $table->dropColumn('job_title');
            });
        }

        if (Schema::hasTable('guests')) {
            Schema::table('guests', function (Blueprint $table) {
                if (Schema::hasColumn('guests', 'first_name')) $table->dropColumn('first_name');
                if (Schema::hasColumn('guests', 'father_name')) $table->dropColumn('father_name');
                if (Schema::hasColumn('guests', 'last_name')) $table->dropColumn('last_name');
                if (Schema::hasColumn('guests', 'nationality')) $table->dropColumn('nationality');
                if (Schema::hasColumn('guests', 'birth_date')) $table->dropColumn('birth_date');
                if (Schema::hasColumn('guests', 'id_type')) $table->dropColumn('id_type');
                if (Schema::hasColumn('guests', 'id_number')) $table->dropColumn('id_number');
                if (Schema::hasColumn('guests', 'attachments')) $table->dropColumn('attachments');
            });
        }
    }
};

