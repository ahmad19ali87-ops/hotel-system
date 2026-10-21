<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\FileSecurityService;
use App\Services\ThreatAutoResponseEngine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ThreatAutoResponseEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->timestamp('locked_until')->nullable();
                $table->string('role')->default('guest');
                $table->boolean('is_verified')->default(false);
                $table->string('verification_code', 10)->nullable();
                $table->timestamp('verification_code_sent_at')->nullable();
                $table->unsignedInteger('verification_attempts')->default(0);
                $table->string('google_id')->nullable();
                $table->string('avatar')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('users', 'locked_until')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('locked_until')->nullable();
            });
        }

        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('blocked_ips')) {
            Schema::create('blocked_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->string('reason', 255)->nullable();
                $table->timestamp('blocked_until')->nullable();
                $table->unsignedInteger('violations_count')->default(1);
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->string('country_code', 10)->nullable()->index();
                $table->string('country_name', 100)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event_type', 100);
                $table->string('severity', 20)->default('warning');
                $table->string('action_taken', 100)->nullable();
                $table->string('method', 10)->nullable();
                $table->string('url', 500)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('security_logs', 'action_taken')) {
            Schema::table('security_logs', function (Blueprint $table) {
                $table->string('action_taken', 100)->nullable();
            });
        }

        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('general');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('app_notifications')) {
            Schema::create('app_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('hotel_id')->nullable();
                $table->string('type')->default('general');
                $table->string('title');
                $table->text('message');
                $table->string('icon')->default('🔔');
                $table->string('link')->nullable();
                $table->json('data')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }

        Cache::flush();
        BlockedIp::query()->delete();
        SecurityLog::query()->delete();
        AppNotification::query()->delete();
    }

    /**
     * Test 1: SQL Injection triggers instant IP ban.
     */
    public function test_sql_injection_triggers_instant_ip_ban()
    {
        $attackerIp = '198.51.100.99';

        $this->assertFalse(BlockedIp::isBlocked($attackerIp));

        // Send a request with a classic SQL injection signature
        $response = $this->withServerVariables(['REMOTE_ADDR' => $attackerIp])
            ->get('/api/hotels?search=' . urlencode("' UNION SELECT 1,2,3,database() -- "));

        $response->assertStatus(403);
        $this->assertStringContainsString('SQL Injection', $response->json('message'));

        // Verify IP was immediately and indefinitely blocked by ThreatAutoResponseEngine
        $this->assertTrue(BlockedIp::isBlocked($attackerIp));
        $block = BlockedIp::getActiveBlock($attackerIp);
        $this->assertNotNull($block);
        $this->assertNull($block->blocked_until); // Permanent block
        $this->assertStringContainsString('SQL Injection', $block->reason);

        // Verify subsequent requests from this IP are blocked by CheckBlockedIp middleware
        $subsequent = $this->withServerVariables(['REMOTE_ADDR' => $attackerIp])
            ->get('/api/hotels');

        $subsequent->assertStatus(403);
        $this->assertStringContainsString('تم حظر عنوان IP', $subsequent->json('message'));
    }

    /**
     * Test 2: Malicious file upload triggers 24h IP ban + instant alarm.
     */
    public function test_malicious_file_upload_triggers_ip_ban_and_instant_alarm()
    {
        $attackerIp = '198.51.100.88';

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@hotel.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        $fakePhpShell = UploadedFile::fake()->createWithContent(
            'shell.php',
            '<' . '?php eval($_POST["cmd"]); ?' . '>'
        );

        $this->assertFalse(BlockedIp::isBlocked($attackerIp));

        // Attempt upload through FileSecurityService
        try {
            request()->server->set('REMOTE_ADDR', $attackerIp);
            FileSecurityService::validateImage($fakePhpShell);
            $this->fail('Expected ValidationException was not thrown for PHP shell upload');
        } catch (ValidationException $e) {
            // Expected validation failure
        }

        // Verify IP was blocked for 24 hours
        $this->assertTrue(BlockedIp::isBlocked($attackerIp));
        $block = BlockedIp::getActiveBlock($attackerIp);
        $this->assertNotNull($block);
        $this->assertNotNull($block->blocked_until);
        $this->assertTrue($block->blocked_until->isFuture());
        $this->assertStringContainsString('محاولة رفع ملف خبيث', $block->reason);

        // Verify critical notification sent to super admin
        $notification = AppNotification::where('user_id', $superAdmin->id)
            ->where('type', 'security_alert')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('إنذار أمني عاجل', $notification->title);
    }

    /**
     * Test 3: Attack from watched country triggers 24h IP ban.
     */
    public function test_attack_from_watched_country_triggers_24h_ban()
    {
        SiteSetting::set('security_alert_countries', 'RU,CN,KP', 'security');

        $ip = '185.220.101.5';

        $this->assertFalse(BlockedIp::isBlocked($ip));

        ThreatAutoResponseEngine::handleWatchedCountryAttack($ip, 'RU', 'Russia', 'sql_injection_attempt');

        $this->assertTrue(BlockedIp::isBlocked($ip));
        $block = BlockedIp::getActiveBlock($ip);
        $this->assertNotNull($block);
        $this->assertNotNull($block->blocked_until);
        $this->assertTrue($block->blocked_until->isFuture());
        $this->assertStringContainsString('دولة مراقبة', $block->reason);
    }

    /**
     * Test 4: Brute force attack triggers 1-hour account lockout.
     */
    public function test_brute_force_attack_triggers_one_hour_account_lockout()
    {
        $user = User::create([
            'name' => 'Target User',
            'email' => 'victim@hotel.com',
            'password' => Hash::make('correct_password_123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        // Create an active token for the user to verify revocation
        $token = $user->createToken('active-session')->plainTextToken;
        $this->assertCount(1, $user->tokens);

        $attackerIp = '203.0.113.44';

        // Perform 4 failed login attempts
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->withServerVariables(['REMOTE_ADDR' => $attackerIp])
                ->postJson('/api/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong_guess_' . $i,
                ]);

            $res->assertStatus(422);
            $this->assertFalse($user->fresh()->isLocked());
        }

        // 5th failed attempt triggers brute force auto-response
        $res5 = $this->withServerVariables(['REMOTE_ADDR' => $attackerIp])
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong_guess_5',
            ]);

        $res5->assertStatus(423); // Locked
        $this->assertStringContainsString('تم قفل الحساب تلقائياً لمدة ساعة', $res5->json('message'));

        // Verify user account is locked and all tokens revoked
        $freshUser = $user->fresh();
        $this->assertTrue($freshUser->isLocked());
        $this->assertNotNull($freshUser->locked_until);
        $this->assertTrue($freshUser->locked_until->isFuture());
        $this->assertCount(0, $freshUser->tokens);

        // Attempting to login with CORRECT password while account is locked from another IP returns 423 Locked
        $resCorrect = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.50'])
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'correct_password_123',
            ]);

        $resCorrect->assertStatus(423);
        $this->assertStringContainsString('تم قفل هذا الحساب مؤقتاً', $resCorrect->json('message'));

        // Super Admin unlocks the account and unblocks IP
        $freshUser->unlock();
        $this->assertFalse($freshUser->fresh()->isLocked());
        BlockedIp::unblock($attackerIp);

        // Now login succeeds
        $resUnlocked = $this->withServerVariables(['REMOTE_ADDR' => $attackerIp])
            ->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'correct_password_123',
            ]);

        $resUnlocked->assertStatus(200);
        $this->assertNotNull($resUnlocked->json('token'));
    }

    /**
     * Test 5: Replay attack terminates session and revokes tokens completely.
     */
    public function test_replay_attack_terminates_session_and_revokes_token()
    {
        $user = User::create([
            'name' => 'Replay Victim',
            'email' => 'replay@hotel.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $tokenResult = $user->createToken('session-token');
        $token = $tokenResult->plainTextToken;

        $nonce = 'unique_nonce_' . bin2hex(random_bytes(8));

        // Request 1: Valid nonce
        $res1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Nonce' => $nonce,
        ])->actingAs($user)->postJson('/api/chat/send', [
            'body' => 'Initial valid request',
        ]);

        // Request 2: Replay of exact same nonce -> triggers Replay Attack Response
        $res2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Nonce' => $nonce,
        ])->actingAs($user)->postJson('/api/chat/send', [
            'body' => 'Initial valid request',
        ]);

        $res2->assertStatus(409);
        $this->assertStringContainsString('تم إنهاء وحظر الجلسة والتوكن بالكامل', $res2->json('message'));

        // Verify all user tokens were deleted
        $this->assertCount(0, $user->fresh()->tokens);

        // Verify bearer token is blacklisted in cache
        $tokenHash = hash('sha256', $token);
        $this->assertTrue(Cache::has('blacklisted_bearer:' . $tokenHash));

        // Subsequent request using blacklisted bearer is rejected with 401
        $res3 = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.77'])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson('/api/chat/send', [
                'body' => 'Unauthorized retry with dead token',
            ]);

        $res3->assertStatus(401);
        $this->assertStringContainsString('تم إنهاء صلاحية وحظر هذه الجلسة', $res3->json('message'));
    }

    /**
     * Test 6: Security Dashboard reflects Threat Auto-Response Engine rules and locked accounts.
     */
    public function test_dashboard_displays_threat_engine_and_admin_can_unlock_user()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin Master',
            'email' => 'master@hotel.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        $lockedUser = User::create([
            'name' => 'Locked User',
            'email' => 'locked@hotel.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => true,
            'locked_until' => now()->addHour(),
        ]);

        $response = $this->actingAs($superAdmin)->get('/platform/security');
        $response->assertStatus(200);
        $response->assertSee('محرك الاستجابة التلقائي للهجمات');
        $response->assertSee('هجمات SQL Injection');
        $response->assertSee('رفع ملف ضار');
        $response->assertSee('هجوم من دولة مراقبة');
        $response->assertSee('هجوم التخمين (Brute Force)');
        $response->assertSee('هجوم Replay');
        $response->assertSee('locked@hotel.com');

        // Admin unlocks user via dashboard route
        $unlockResponse = $this->actingAs($superAdmin)
            ->post("/platform/security/unlock-user/{$lockedUser->id}");

        $unlockResponse->assertRedirect();
        $this->assertFalse($lockedUser->fresh()->isLocked());

        // Admin updates threat engine configuration
        $configResponse = $this->actingAs($superAdmin)
            ->post('/platform/security/threat-engine', [
                'threat_engine_enabled' => '1',
                'threat_rule_sqli' => '1',
                'threat_rule_file' => '1',
                'threat_rule_country' => '1',
                'threat_rule_bruteforce' => '1',
                'threat_rule_replay' => '1',
            ]);

        $configResponse->assertRedirect();
        $this->assertEquals('1', SiteSetting::get('threat_engine_enabled'));
    }
}
