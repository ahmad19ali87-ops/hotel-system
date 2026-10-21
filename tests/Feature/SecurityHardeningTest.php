<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\CaptchaService;
use App\Services\FileSecurityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
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
                $table->string('role')->default('guest');
                $table->boolean('is_verified')->default(false);
                $table->string('verification_code', 10)->nullable();
                $table->timestamp('verification_code_sent_at')->nullable();
                $table->unsignedInteger('verification_attempts')->default(0);
                $table->string('google_id')->nullable();
                $table->string('avatar')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamp('locked_until')->nullable();
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
                $table->string('title');
                $table->text('message');
                $table->string('type')->default('general');
                $table->string('icon')->default('🔔');
                $table->string('link')->nullable();
                $table->unsignedBigInteger('hotel_id')->nullable();
                $table->text('data')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }

    public function test_backdoor_123456_is_strictly_rejected_in_api()
    {
        $user = User::create([
            'name' => 'Tester User',
            'email' => 'tester@example.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => false,
            'verification_code' => '849201',
            'verification_code_sent_at' => now(),
            'verification_attempts' => 0,
        ]);

        // Attempt using backdoor '123456'
        $response = $this->postJson('/api/auth/verify-code', [
            'email' => 'tester@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertFalse($user->fresh()->is_verified);

        // Attempt using correct code
        $validRes = $this->postJson('/api/auth/verify-code', [
            'email' => 'tester@example.com',
            'code' => '849201',
        ]);

        $validRes->assertStatus(200);
        $this->assertTrue($user->fresh()->is_verified);
        $this->assertNull($user->fresh()->verification_code);
    }

    public function test_verification_code_expires_after_10_minutes()
    {
        $user = User::create([
            'name' => 'Expired Code User',
            'email' => 'expired@example.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => false,
            'verification_code' => '555777',
            'verification_code_sent_at' => now()->subMinutes(12),
            'verification_attempts' => 0,
        ]);

        $response = $this->postJson('/api/auth/verify-code', [
            'email' => 'expired@example.com',
            'code' => '555777',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('انتهت صلاحية', $response->json('message'));
        $this->assertFalse($user->fresh()->is_verified);
    }

    public function test_verification_code_anti_brute_force_lockout()
    {
        $user = User::create([
            'name' => 'Brute Force User',
            'email' => 'brute@example.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => false,
            'verification_code' => '999888',
            'verification_code_sent_at' => now(),
            'verification_attempts' => 0,
        ]);

        // 5 consecutive wrong attempts
        for ($i = 1; $i <= 5; $i++) {
            $res = $this->postJson('/api/auth/verify-code', [
                'email' => 'brute@example.com',
                'code' => '11111' . $i,
            ]);
            $res->assertStatus(422);
        }

        $this->assertEquals(5, $user->fresh()->verification_attempts);

        // 6th attempt is blocked either via lockout (429) or IP block (403)
        $lockedRes = $this->postJson('/api/auth/verify-code', [
            'email' => 'brute@example.com',
            'code' => '999888',
        ]);

        $this->assertTrue(in_array($lockedRes->status(), [403, 429], true));
        $this->assertFalse($user->fresh()->is_verified);

        // Unblock 127.0.0.1 for subsequent tests
        BlockedIp::unblock('127.0.0.1');
    }

    public function test_resend_code_anti_spam_throttling()
    {
        $user = User::create([
            'name' => 'Spam Resend User',
            'email' => 'resend@example.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => false,
            'verification_code' => '112233',
            'verification_code_sent_at' => now(), // Sent right now
            'verification_attempts' => 0,
        ]);

        $res = $this->postJson('/api/auth/resend-code', [
            'email' => 'resend@example.com',
        ]);

        $res->assertStatus(429);
        $this->assertStringContainsString('الانتظار', $res->json('message'));
    }

    public function test_blocked_ip_is_denied_access()
    {
        BlockedIp::block('192.168.1.100', 'Suspicious activity detected', 60);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->getJson('/api/hotels');

        $response->assertStatus(403);
        $this->assertStringContainsString('تم حظر عنوان IP', $response->json('message'));
    }

    public function test_sql_injection_detection_blocks_attack()
    {
        $response = $this->getJson('/api/hotels?search=' . urlencode("' UNION SELECT * FROM users --"));

        $response->assertStatus(403);
        $this->assertStringContainsString('SQL Injection', $response->json('message'));
    }

    public function test_file_security_service_rejects_php_and_double_extensions()
    {
        // 1. Direct .php upload attempt
        $phpFile = UploadedFile::fake()->create('shell.php', 10, 'text/x-php');
        $this->expectException(ValidationException::class);
        FileSecurityService::validateImage($phpFile);
    }

    public function test_file_security_service_rejects_embedded_php_in_image()
    {
        // 2. Embedded PHP code inside fake png
        $maliciousContent = "\x89PNG\r\n\x1a\n" . "<?php system(\$_GET['cmd']); ?>";
        $evilFile = UploadedFile::fake()->createWithContent('avatar.png', $maliciousContent);

        try {
            FileSecurityService::validateImage($evilFile);
            $this->fail('FileSecurityService should have thrown ValidationException for embedded PHP');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_arabic_math_captcha_service_flow()
    {
        $captcha = CaptchaService::generate();
        $this->assertNotEmpty($captcha['question']);
        $this->assertNotEmpty(session('math_captcha_token'));

        // Wrong answer fails
        $this->assertFalse(CaptchaService::verify('9999'));

        // Regenerate and test valid
        session(['math_captcha_token' => hash('sha256', '15' . config('app.key'))]);
        $this->assertTrue(CaptchaService::verify('15'));
        // Second check fails because token is invalidated immediately
        $this->assertFalse(CaptchaService::verify('15'));
    }

    public function test_api_replay_attack_prevention()
    {
        $user = User::create([
            'name' => 'Nonce Tester',
            'email' => 'nonce@example.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $nonce = 'test_unique_nonce_12345';

        // First request with nonce passes
        $res1 = $this->withHeaders([
            'X-Nonce' => $nonce,
        ])->actingAs($user)->postJson('/api/chat/send', [
            'body' => 'Hello hotel',
        ]);

        // Second request with SAME nonce is blocked as replay attack (409 Conflict)
        $res2 = $this->withHeaders([
            'X-Nonce' => $nonce,
        ])->actingAs($user)->postJson('/api/chat/send', [
            'body' => 'Hello hotel duplicate',
        ]);

        $res2->assertStatus(409);
        $this->assertStringContainsString('Replay Attack', $res2->json('message'));
    }

    public function test_super_admin_can_access_security_dashboard_and_see_breakdowns()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin Officer',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        // Seed some security incidents
        SecurityLog::create([
            'ip_address' => '185.220.101.5',
            'country_code' => 'DE',
            'country_name' => 'ألمانيا',
            'event_type' => 'sql_injection_attempt',
            'severity' => 'critical',
            'message' => 'Union select attack blocked',
            'created_at' => now()->subHour(),
        ]);

        SecurityLog::create([
            'ip_address' => '185.220.101.5',
            'country_code' => 'DE',
            'country_name' => 'ألمانيا',
            'event_type' => 'malicious_file_upload',
            'severity' => 'critical',
            'message' => 'PHP shell upload blocked',
            'created_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($superAdmin)->get('/platform/security');

        $response->assertStatus(200);
        $response->assertSee('لوحة المراقبة والأمان الذكية');
        $response->assertSee('حقن SQL Injection');
        $response->assertSee('ألمانيا');
        $response->assertSee('185.220.101.5');
    }

    public function test_guest_or_regular_user_cannot_access_security_dashboard()
    {
        $guest = User::create([
            'name' => 'Regular Visitor',
            'email' => 'visitor@example.com',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $response = $this->actingAs($guest)->get('/platform/security');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_manually_block_and_unblock_ip()
    {
        $superAdmin = User::create([
            'name' => 'Security Admin',
            'email' => 'secadmin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        $targetIp = '203.0.113.88';

        // Block IP
        $blockRes = $this->actingAs($superAdmin)->post('/platform/security/block', [
            'ip_address' => $targetIp,
            'duration' => '24',
            'reason' => 'حظر أمني يدوي للتجربة',
        ]);

        $blockRes->assertRedirect();
        $this->assertTrue(BlockedIp::isBlocked($targetIp));

        // Unblock IP
        $unblockRes = $this->actingAs($superAdmin)->post('/platform/security/unblock', [
            'ip_address' => $targetIp,
        ]);

        $unblockRes->assertRedirect();
        $this->assertFalse(BlockedIp::isBlocked($targetIp));
    }

    public function test_super_admin_can_update_security_alert_rules()
    {
        $superAdmin = User::create([
            'name' => 'Rule Manager',
            'email' => 'rulemanager@example.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        $res = $this->actingAs($superAdmin)->post('/platform/security/settings', [
            'security_alert_countries' => 'RU,CN,US,IL,IR,TR',
            'security_alert_threshold' => 3,
        ]);

        $res->assertRedirect();
        $this->assertEquals('RU,CN,US,IL,IR,TR', \App\Models\SiteSetting::get('security_alert_countries'));
        $this->assertEquals('3', \App\Models\SiteSetting::get('security_alert_threshold'));
    }

    public function test_critical_attack_triggers_instant_alert_notification_to_super_admin()
    {
        $superAdmin = User::create([
            'name' => 'Super Admin Watcher',
            'email' => 'watcher@example.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_verified' => true,
        ]);

        // Record a critical attack
        SecurityLog::record(
            'sql_injection_attempt',
            'critical',
            'UNION SELECT injection attempt on /api/hotels',
            null,
            null
        );

        // Verify instant notification was created for super admin
        $notif = \App\Models\AppNotification::where('user_id', $superAdmin->id)
            ->where('type', 'security_alert')
            ->first();

        $this->assertNotNull($notif);
        $this->assertStringContainsString('إنذار أمني فوري', $notif->title);
    }
}
