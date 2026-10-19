<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\Room;
use App\Models\Guest;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Maintenance;
use App\Models\CleaningLog;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class BackupController extends Controller
{
    public function index(Request $request, ?Hotel $hotel = null)
    {
        $targetHotel = $this->resolveHotel($hotel);

        $counts = [
            'rooms' => Room::where('hotel_id', $targetHotel->id)->count(),
            'room_types' => RoomType::where('hotel_id', $targetHotel->id)->count(),
            'guests' => Guest::where('hotel_id', $targetHotel->id)->count(),
            'bookings' => Booking::where('hotel_id', $targetHotel->id)->count(),
            'invoices' => Invoice::where('hotel_id', $targetHotel->id)->count(),
            'accounts' => Account::where('hotel_id', $targetHotel->id)->count(),
        ];

        return view('admin.backup.index', [
            'hotel' => $targetHotel,
            'counts' => $counts,
            'isPlatform' => auth()->user()->role === 'super_admin' || auth()->user()->role === 'platform_admin',
        ]);
    }

    public function download(Request $request, ?Hotel $hotel = null)
    {
        $targetHotel = $this->resolveHotel($hotel);

        // جمع كافة بيانات الفندق
        $roomTypes = RoomType::where('hotel_id', $targetHotel->id)->get();
        $rooms = Room::where('hotel_id', $targetHotel->id)->get();
        $guests = Guest::where('hotel_id', $targetHotel->id)->get();
        $bookings = Booking::where('hotel_id', $targetHotel->id)->get();
        $invoices = Invoice::where('hotel_id', $targetHotel->id)->get();
        $invoiceIds = $invoices->pluck('id')->toArray();
        $payments = !empty($invoiceIds) ? Payment::whereIn('invoice_id', $invoiceIds)->get() : collect();
        $accounts = Account::where('hotel_id', $targetHotel->id)->get();
        $maintenance = Maintenance::where('hotel_id', $targetHotel->id)->get();
        $cleaningLogs = CleaningLog::where('hotel_id', $targetHotel->id)->get();

        $backupData = [
            'system' => 'Hotel Management Cloud System',
            'version' => '2.0',
            'exported_at' => now()->toIso8601String(),
            'hotel_id' => $targetHotel->id,
            'hotel_name' => $targetHotel->name,
            'hotel_slug' => $targetHotel->slug,
            'hotel' => $targetHotel->makeHidden(['id'])->toArray(),
            'data' => [
                'room_types' => $roomTypes->toArray(),
                'rooms' => $rooms->toArray(),
                'guests' => $guests->toArray(),
                'bookings' => $bookings->toArray(),
                'invoices' => $invoices->toArray(),
                'payments' => $payments->toArray(),
                'accounts' => $accounts->toArray(),
                'maintenance' => $maintenance->toArray(),
                'cleaning_logs' => $cleaningLogs->toArray(),
            ],
            'summary' => [
                'rooms_count' => $rooms->count(),
                'guests_count' => $guests->count(),
                'bookings_count' => $bookings->count(),
                'invoices_count' => $invoices->count(),
                'accounts_count' => $accounts->count(),
            ],
        ];

        $json = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $fileName = 'backup_' . $targetHotel->slug . '_' . now()->format('Y-m-d_H-i-s') . '.json';

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'تصدير نسخة احتياطية',
            'details' => "تم تحميل نسخة احتياطية لبيانات فندق {$targetHotel->name}",
        ]);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function restore(Request $request, ?Hotel $hotel = null)
    {
        $targetHotel = $this->resolveHotel($hotel);

        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:json,txt', 'max:51200'],
            'restore_mode' => ['required', 'string', 'in:merge,replace'],
        ], [
            'backup_file.required' => 'يرجى اختيار ملف النسخة الاحتياطية (.json).',
            'backup_file.mimes' => 'يجب أن يكون الملف بصيغة JSON صالحة.',
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $backup = json_decode($content, true);

        if (!$backup || !isset($backup['data']) || !is_array($backup['data'])) {
            return back()->with('error', 'ملف النسخة الاحتياطية غير صالح أو تالف.');
        }

        $mode = $request->restore_mode;
        $restoredCounts = [];

        DB::transaction(function () use ($targetHotel, $backup, $mode, &$restoredCounts) {
            $data = $backup['data'];

            // في حال الاستبدال الكامل: حذف البيانات التشغيلية القديمة للفندق أولاً
            if ($mode === 'replace') {
                Payment::whereIn('invoice_id', Invoice::where('hotel_id', $targetHotel->id)->pluck('id'))->delete();
                Invoice::where('hotel_id', $targetHotel->id)->delete();
                Booking::where('hotel_id', $targetHotel->id)->delete();
                Account::where('hotel_id', $targetHotel->id)->delete();
                Maintenance::where('hotel_id', $targetHotel->id)->delete();
                CleaningLog::where('hotel_id', $targetHotel->id)->delete();
                Room::where('hotel_id', $targetHotel->id)->delete();
                RoomType::where('hotel_id', $targetHotel->id)->delete();
                Guest::where('hotel_id', $targetHotel->id)->delete();
            }

            // 1. استعادة أنواع الغرف
            $roomTypeMap = [];
            foreach ($data['room_types'] ?? [] as $rt) {
                $oldId = $rt['id'] ?? null;
                unset($rt['id'], $rt['created_at'], $rt['updated_at']);
                $rt['hotel_id'] = $targetHotel->id;

                $newRt = RoomType::updateOrCreate(
                    ['hotel_id' => $targetHotel->id, 'name' => $rt['name']],
                    $rt
                );
                if ($oldId) $roomTypeMap[$oldId] = $newRt->id;
            }
            $restoredCounts['room_types'] = count($data['room_types'] ?? []);

            // 2. استعادة الغرف
            $roomMap = [];
            foreach ($data['rooms'] ?? [] as $rm) {
                $oldId = $rm['id'] ?? null;
                $oldRtId = $rm['room_type_id'] ?? null;
                unset($rm['id'], $rm['created_at'], $rm['updated_at']);
                $rm['hotel_id'] = $targetHotel->id;
                if ($oldRtId && isset($roomTypeMap[$oldRtId])) {
                    $rm['room_type_id'] = $roomTypeMap[$oldRtId];
                }

                $newRm = Room::updateOrCreate(
                    ['hotel_id' => $targetHotel->id, 'room_number' => $rm['room_number']],
                    $rm
                );
                if ($oldId) $roomMap[$oldId] = $newRm->id;
            }
            $restoredCounts['rooms'] = count($data['rooms'] ?? []);

            // 3. استعادة النزلاء
            $guestMap = [];
            foreach ($data['guests'] ?? [] as $gst) {
                $oldId = $gst['id'] ?? null;
                unset($gst['id'], $gst['created_at'], $gst['updated_at']);
                $gst['hotel_id'] = $targetHotel->id;

                $matchCriteria = ['hotel_id' => $targetHotel->id];
                if (!empty($gst['id_number'])) {
                    $matchCriteria['id_number'] = $gst['id_number'];
                } else {
                    $matchCriteria['name'] = $gst['name'];
                }

                $newGst = Guest::updateOrCreate($matchCriteria, $gst);
                if ($oldId) $guestMap[$oldId] = $newGst->id;
            }
            $restoredCounts['guests'] = count($data['guests'] ?? []);

            // 4. استعادة الحجوزات
            $bookingMap = [];
            foreach ($data['bookings'] ?? [] as $bk) {
                $oldId = $bk['id'] ?? null;
                $oldGstId = $bk['guest_id'] ?? null;
                $oldRmId = $bk['room_id'] ?? null;
                unset($bk['id'], $bk['created_at'], $bk['updated_at']);

                $bk['hotel_id'] = $targetHotel->id;
                if ($oldGstId && isset($guestMap[$oldGstId])) {
                    $bk['guest_id'] = $guestMap[$oldGstId];
                }
                if ($oldRmId && isset($roomMap[$oldRmId])) {
                    $bk['room_id'] = $roomMap[$oldRmId];
                }

                $newBk = Booking::create($bk);
                if ($oldId) $bookingMap[$oldId] = $newBk->id;
            }
            $restoredCounts['bookings'] = count($data['bookings'] ?? []);

            // 5. استعادة الفواتير
            $invoiceMap = [];
            foreach ($data['invoices'] ?? [] as $inv) {
                $oldId = $inv['id'] ?? null;
                $oldBkId = $inv['booking_id'] ?? null;
                $oldGstId = $inv['guest_id'] ?? null;
                unset($inv['id'], $inv['created_at'], $inv['updated_at']);

                $inv['hotel_id'] = $targetHotel->id;
                if ($oldBkId && isset($bookingMap[$oldBkId])) {
                    $inv['booking_id'] = $bookingMap[$oldBkId];
                }
                if ($oldGstId && isset($guestMap[$oldGstId])) {
                    $inv['guest_id'] = $guestMap[$oldGstId];
                }

                $newInv = Invoice::create($inv);
                if ($oldId) $invoiceMap[$oldId] = $newInv->id;
            }
            $restoredCounts['invoices'] = count($data['invoices'] ?? []);

            // 6. استعادة الدفعات
            foreach ($data['payments'] ?? [] as $pay) {
                $oldInvId = $pay['invoice_id'] ?? null;
                unset($pay['id'], $pay['created_at'], $pay['updated_at']);
                if ($oldInvId && isset($invoiceMap[$oldInvId])) {
                    $pay['invoice_id'] = $invoiceMap[$oldInvId];
                    Payment::create($pay);
                }
            }

            // 7. استعادة الحسابات المالية
            foreach ($data['accounts'] ?? [] as $acc) {
                unset($acc['id'], $acc['created_at'], $acc['updated_at']);
                $acc['hotel_id'] = $targetHotel->id;
                Account::create($acc);
            }
            $restoredCounts['accounts'] = count($data['accounts'] ?? []);
        });

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'استعادة نسخة احتياطية',
            'details' => "تمت استعادة نسخة احتياطية لفندق {$targetHotel->name} بنجاح (وضع {$mode})",
        ]);

        $summaryText = "تمت استعادة بيانات الفندق بنجاح! تم استيراد: " .
            "{$restoredCounts['rooms']} غرف، " .
            "{$restoredCounts['guests']} نزلاء، " .
            "{$restoredCounts['bookings']} حجوزات، " .
            "{$restoredCounts['invoices']} فواتير، " .
            "{$restoredCounts['accounts']} قيود محاسبية.";

        return back()->with('success', $summaryText);
    }

    private function resolveHotel(?Hotel $hotel): Hotel
    {
        $user = auth()->user();

        if ($hotel && $hotel->exists) {
            if ($user->role === 'platform_admin') {
                abort_unless($user->hasPermission('backups'), 403, 'غير مصرح لك بإدارة النسخ الاحتياطية للفنادق.');
            }
            if (in_array($user->role, ['super_admin', 'platform_admin'], true)) {
                return $hotel;
            }
            abort_unless((int)$user->hotel_id === (int)$hotel->id, 403);
            return $hotel;
        }

        abort_unless($user && $user->hotel_id, 403, 'غير مصرح لك بإدارة هذا الفندق.');
        return Hotel::findOrFail($user->hotel_id);
    }
}
