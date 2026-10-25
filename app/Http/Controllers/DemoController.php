<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoController extends Controller
{
    /**
     * Enter Demo Mode: Provision or reset sandbox demo hotel and login as demo manager.
     */
    public function enter(Request $request)
    {
        // 1. Get or create the isolated Demo Hotel
        $demoHotel = Hotel::firstOrCreate(
            ['slug' => 'demo-hotel'],
            [
                'name' => 'فندق اللؤلؤة التجريبي (بيئة تجريبية)',
                'slug' => 'demo-hotel',
                'stars' => 5,
                'province' => 'دمشق',
                'address' => 'شارع الثورة - المنطقة الدبلوماسية',
                'phone' => '+963 11 000000',
                'email' => 'demo@hotel-system.local',
                'tagline' => 'تجربة حية لكافة مزايا نظام إدارة الفنادق المتكامل',
                'description' => 'بيئة تجريبية تفاعلية تتيح لعملاء المنصة تجربة كافة الوظائف من إدارة الغرف، الحجوزات، الفواتير، ونظام المحاسبة المزدوج.',
                'is_visible' => false, // Hidden from public client directory
            ]
        );

        // 2. Get or create Demo Hotel Manager user
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@hotel-system.local'],
            [
                'name' => 'مدير تجريبي (Demo Manager)',
                'email' => 'demo@hotel-system.local',
                'password' => Hash::make('demo123456'),
                'role' => 'hotel_admin',
                'hotel_id' => $demoHotel->id,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Ensure user is linked to demo hotel
        if ((int) $demoUser->hotel_id !== (int) $demoHotel->id) {
            $demoUser->update(['hotel_id' => $demoHotel->id, 'role' => 'hotel_admin']);
        }

        // 3. Ensure baseline demo data exists
        $this->seedDemoBaseline($demoHotel, $demoUser);

        // 4. Log the user in & mark session as Demo
        Auth::login($demoUser);
        $request->session()->put('is_demo_session', true);
        $request->session()->put('demo_hotel_id', $demoHotel->id);

        return redirect()->route('admin.dashboard')->with('status', '⚡ مرحباً بك في النسخة التجريبية (Demo)! يمكنك استعراض وتجربة كافة وظائف النظام وإدارة الفندق بحرية.');
    }

    /**
     * Reset Demo Hotel data back to clean baseline.
     */
    public function reset(Request $request)
    {
        $demoHotel = Hotel::where('slug', 'demo-hotel')->first();
        if ($demoHotel) {
            $demoUser = User::where('email', 'demo@hotel-system.local')->first();
            $this->cleanAndRebaseline($demoHotel, $demoUser);
        }

        return redirect()->route('admin.dashboard')->with('success', '🔄 تم تفريغ وإعادة تعيين بيانات الفندق التجريبي بنجاح إلى حالتها الأولية.')->with('status', '🔄 تم تفريغ وإعادة تعيين بيانات الفندق التجريبي بنجاح إلى حالتها الأولية.');
    }

    /**
     * Exit Demo Mode: Wipe sandbox temporary data and logout.
     */
    public function exit(Request $request)
    {
        $demoHotel = Hotel::where('slug', 'demo-hotel')->first();
        if ($demoHotel) {
            if (Schema::hasTable('invoices')) Invoice::where('hotel_id', $demoHotel->id)->delete();
            if (Schema::hasTable('bookings')) Booking::where('hotel_id', $demoHotel->id)->delete();
            if (Schema::hasTable('guests')) Guest::where('hotel_id', $demoHotel->id)->delete();
            if (Schema::hasTable('accounts')) Account::where('hotel_id', $demoHotel->id)->delete();
            if (Schema::hasTable('rooms')) Room::where('hotel_id', $demoHotel->id)->delete();
            if (Schema::hasTable('room_types')) RoomType::where('hotel_id', $demoHotel->id)->delete();
            $demoHotel->forceDelete();
        }

        User::where('email', 'demo@hotel-system.local')->forceDelete();

        Auth::logout();
        $request->session()->forget(['is_demo_session', 'demo_hotel_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'شكراً لتجربتك لوحة إدارة النظام. تم إنهاء الجلسة التجريبية وتفريغ كافة البيانات بنجاح.');
    }

    /**
     * Clean all dynamic demo records and re-seed clean baseline.
     */
    private function cleanAndRebaseline(Hotel $hotel, ?User $user): void
    {
        // Delete all bookings, invoices, guests, accounts for demo hotel
        if (Schema::hasTable('invoices')) Invoice::where('hotel_id', $hotel->id)->delete();
        if (Schema::hasTable('bookings')) Booking::where('hotel_id', $hotel->id)->delete();
        if (Schema::hasTable('guests')) Guest::where('hotel_id', $hotel->id)->delete();
        if (Schema::hasTable('accounts')) Account::where('hotel_id', $hotel->id)->delete();
        if (Schema::hasTable('rooms')) Room::where('hotel_id', $hotel->id)->delete();
        if (Schema::hasTable('room_types')) RoomType::where('hotel_id', $hotel->id)->delete();

        if ($user) {
            $this->seedDemoBaseline($hotel, $user);
        }
    }

    /**
     * Seed initial demo data for smooth exploration.
     */
    private function seedDemoBaseline(Hotel $hotel, User $user): void
    {
        if (Room::where('hotel_id', $hotel->id)->count() > 0) {
            return; // Baseline already present
        }

        $deluxeId = null;
        $suiteId = null;
        $doubleId = null;

        // Room Types
        if (Schema::hasTable('room_types')) {
            $suite = RoomType::create([
                'hotel_id' => $hotel->id,
                'name' => 'جناح ملكي فاخر (Royal Suite)',
                'base_price' => 350000,
                'description' => 'جناح فاخر مع إطلالة بانورامية وشرفة خاصة وصالون ضيافة منفصل.',
            ]);
            $suiteId = $suite->id;

            $deluxe = RoomType::create([
                'hotel_id' => $hotel->id,
                'name' => 'غرفة ديلوكس مفردة (Deluxe Single)',
                'base_price' => 180000,
                'description' => 'غرفة أنيقة مجهزة بسرير ماستر عريض ومكتب عمل متكامل.',
            ]);
            $deluxeId = $deluxe->id;

            $double = RoomType::create([
                'hotel_id' => $hotel->id,
                'name' => 'غرفة مزدوجة كينغ (Double King)',
                'base_price' => 240000,
                'description' => 'مثالية للأزواج والعائلات مع سريرين وميني بار.',
            ]);
            $doubleId = $double->id;
        }

        // Rooms
        $makeRoom = function($hotelId, $typeId, $number, $priceSyp, $priceUsd, $status, $needsCleaning = false) {
            $data = [
                'hotel_id' => $hotelId,
                'room_number' => $number,
                'type' => 'Standard',
                'price' => $priceSyp,
                'status' => $status,
            ];
            if (Schema::hasColumn('rooms', 'price_syp')) $data['price_syp'] = $priceSyp;
            if (Schema::hasColumn('rooms', 'price_usd')) $data['price_usd'] = $priceUsd;
            if ($typeId && Schema::hasColumn('rooms', 'room_type_id')) $data['room_type_id'] = $typeId;
            if ($needsCleaning && Schema::hasColumn('rooms', 'needs_cleaning')) $data['needs_cleaning'] = true;
            return Room::create($data);
        };

        $r101 = $makeRoom($hotel->id, $deluxeId, '101', 180000, 50, 'occupied');
        $r102 = $makeRoom($hotel->id, $doubleId, '102', 240000, 65, 'available');
        $r201 = $makeRoom($hotel->id, $suiteId, '201', 350000, 95, 'booked');
        $r202 = $makeRoom($hotel->id, $deluxeId, '202', 180000, 50, 'cleaning', true);
        $r301 = $makeRoom($hotel->id, $suiteId, '301', 350000, 95, 'available');

        // Guests
        $makeGuest = function($hotelId, $name, $phone, $email, $nat, $idType, $idNumber) {
            $data = [
                'hotel_id' => $hotelId,
                'name' => $name,
                'phone' => $phone,
                'nationality' => $nat,
                'id_number' => $idNumber,
            ];
            if (Schema::hasColumn('guests', 'email')) $data['email'] = $email;
            if (Schema::hasColumn('guests', 'id_type')) $data['id_type'] = $idType;
            return Guest::create($data);
        };

        $g1 = $makeGuest($hotel->id, 'المهندس رامي الخالد', '0944111222', 'rami@example.test', 'سوري', 'هوية وطنية', '01020304050');
        $g2 = $makeGuest($hotel->id, 'السيد مايكل براون', '+971 50 1234567', 'michael@example.test', 'إماراتي / مقيم', 'جواز سفر', 'A98765432');

        // Bookings
        $b1Data = [
            'hotel_id' => $hotel->id,
            'room_id' => $r101->id,
            'guest_id' => $g1->id,
            'check_in' => Carbon::now()->subDays(1)->toDateString(),
            'check_out' => Carbon::now()->addDays(2)->toDateString(),
            'currency' => 'SYP',
            'total_price' => 540000,
            'total_price_usd' => 150,
            'status' => 'checked_in',
            'created_by' => $user->id,
        ];
        if (Schema::hasColumn('bookings', 'guest_name')) $b1Data['guest_name'] = $g1->name;
        if (Schema::hasColumn('bookings', 'guest_phone')) $b1Data['guest_phone'] = $g1->phone;
        $b1 = Booking::create($b1Data);

        $b2Data = [
            'hotel_id' => $hotel->id,
            'room_id' => $r201->id,
            'guest_id' => $g2->id,
            'check_in' => Carbon::now()->addDays(1)->toDateString(),
            'check_out' => Carbon::now()->addDays(4)->toDateString(),
            'currency' => 'USD',
            'total_price' => 285,
            'total_price_usd' => 285,
            'status' => 'confirmed',
            'created_by' => $user->id,
        ];
        if (Schema::hasColumn('bookings', 'guest_name')) $b2Data['guest_name'] = $g2->name;
        if (Schema::hasColumn('bookings', 'guest_phone')) $b2Data['guest_phone'] = $g2->phone;
        $b2 = Booking::create($b2Data);

        // Invoices
        if (Schema::hasTable('invoices')) {
            Invoice::create([
                'hotel_id' => $hotel->id,
                'booking_id' => $b1->id,
                'room_id' => $r101->id,
                'invoice_number' => 'DEMO-INV-001',
                'amount' => 540000,
                'total' => 540000,
                'currency' => 'SYP',
                'status' => 'paid',
            ]);

            Invoice::create([
                'hotel_id' => $hotel->id,
                'booking_id' => $b2->id,
                'room_id' => $r201->id,
                'invoice_number' => 'DEMO-INV-002',
                'amount' => 285,
                'total' => 285,
                'currency' => 'USD',
                'status' => 'unpaid',
            ]);
        }

        // Cash / Ledger Account
        if (Schema::hasTable('accounts')) {
            Account::create([
                'hotel_id' => $hotel->id,
                'title' => 'صندوق الاستقبال (Demo Cash Box)',
                'type' => 'income',
                'amount' => 540000,
                'currency' => 'SYP',
                'notes' => 'رصيد افتتاحي تجريبي لصندوق النقدية',
                'transaction_date' => Carbon::now()->toDateString(),
            ]);
        }
    }
}
