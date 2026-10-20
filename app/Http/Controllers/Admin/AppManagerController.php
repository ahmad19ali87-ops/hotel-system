<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use App\Models\AppBroadcast;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppManagerController extends Controller
{
    /**
     * Display the Mobile App Management Dashboard.
     */
    public function index()
    {
        $destinations = Destination::orderBy('sort_order')->latest('id')->get();
        $banners = AppBanner::orderBy('sort_order')->latest('id')->get();
        $hotels = Hotel::select('id', 'name', 'province')->where('is_visible', true)->orderBy('name')->get();

        $provinces = [
            'دمشق', 'ريف دمشق', 'اللاذقية', 'طرطوس', 'حلب', 'حمص', 'حماة',
            'السويداء', 'درعا', 'القنيطرة', 'دير الزور', 'الرقة', 'الحسكة'
        ];

        $broadcastMessages = AppBroadcast::where('type', 'message')->with('sender')->latest()->take(30)->get();
        $broadcastNotifications = AppBroadcast::where('type', 'event_notification')->with('sender')->latest()->take(30)->get();
        $guestsCount = User::where('role', 'guest')->count();
        $totalUsersCount = User::count();

        $welcomeEnabled = SiteSetting::get('app_welcome_enabled', '1') !== '0';
        $welcomeTitle = SiteSetting::get('app_welcome_title', 'أهلاً بك في بوابة حجز الفنادق الذكية 🎉');
        $welcomeMessage = SiteSetting::get('app_welcome_message', 'يسعدنا انضمامك إلينا! استمتع بأفضل العروض الحصرية وإقامة مميزة في أرقى الفنادق.');

        return view('admin.app-manager.index', compact(
            'destinations',
            'banners',
            'hotels',
            'provinces',
            'broadcastMessages',
            'broadcastNotifications',
            'guestsCount',
            'totalUsersCount',
            'welcomeEnabled',
            'welcomeTitle',
            'welcomeMessage'
        ));
    }

    /**
     * Store a newly created destination card for the mobile app.
     */
    public function storeDestination(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('destinations', 'public');
        } elseif (!empty($validated['image_url'])) {
            $imagePath = trim($validated['image_url']);
        }

        Destination::create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'link' => $validated['link'] ?? ('/hotels?province=' . urlencode($validated['title'])),
            'image' => $imagePath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'destinations'])
            ->with('status', 'تمت إضافة بطاقة الوجهة بنجاح إلى قسم استكشف الوجهات في تطبيق الجوال.');
    }

    /**
     * Update an existing destination card.
     */
    public function updateDestination(Request $request, Destination $destination)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
            'delete_image' => ['nullable'],
        ]);

        $imagePath = $destination->image;

        if ($request->has('delete_image')) {
            if ($imagePath && !str_starts_with($imagePath, 'http')) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }

        if ($request->hasFile('image_file')) {
            if ($imagePath && !str_starts_with($imagePath, 'http')) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image_file')->store('destinations', 'public');
        } elseif (!empty($validated['image_url'])) {
            $imagePath = trim($validated['image_url']);
        }

        $destination->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'link' => $validated['link'] ?? $destination->link,
            'image' => $imagePath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'destinations'])
            ->with('status', 'تم تحديث بيانات بطاقة الوجهة والصورة بنجاح.');
    }

    /**
     * Delete a destination card.
     */
    public function destroyDestination(Destination $destination)
    {
        if ($destination->image && !str_starts_with($destination->image, 'http')) {
            Storage::disk('public')->delete($destination->image);
        }

        $destination->delete();

        return redirect()->route('admin.app-manager.index', ['tab' => 'destinations'])
            ->with('status', 'تم حذف بطاقة الوجهة بنجاح.');
    }

    /**
     * Toggle destination active status.
     */
    public function toggleDestination(Destination $destination)
    {
        $destination->update(['is_active' => !$destination->is_active]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'destinations'])
            ->with('status', 'تم تغيير حالة ظهور الوجهة في تطبيق الجوال.');
    }

    /**
     * Store a promotional banner / ad in the mobile app ("أشهر الواجهات والإعلانات").
     */
    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'badge' => ['nullable', 'string', 'max:60'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link_type' => ['required', 'string', 'in:destination,hotel,url,none'],
            'link_value' => ['nullable', 'string', 'max:255'],
            'bg_gradient' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('app_banners', 'public');
        } elseif (!empty($validated['image_url'])) {
            $imagePath = trim($validated['image_url']);
        }

        AppBanner::create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'badge' => $validated['badge'] ?: 'أشهر الواجهات',
            'image' => $imagePath,
            'link_type' => $validated['link_type'],
            'link_value' => $validated['link_value'] ?? null,
            'bg_gradient' => $validated['bg_gradient'] ?? 'linear-gradient(135deg, #003580, #1e3a8a)',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'banners'])
            ->with('status', 'تمت إضافة ونشر الإعلان والكلمات الإعلانية بنجاح في تطبيق الجوال.');
    }

    /**
     * Update an existing app banner / ad.
     */
    public function updateBanner(Request $request, AppBanner $banner)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'badge' => ['nullable', 'string', 'max:60'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link_type' => ['required', 'string', 'in:destination,hotel,url,none'],
            'link_value' => ['nullable', 'string', 'max:255'],
            'bg_gradient' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
            'delete_image' => ['nullable'],
        ]);

        $imagePath = $banner->image;

        if ($request->has('delete_image')) {
            if ($imagePath && !str_starts_with($imagePath, 'http')) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = null;
        }

        if ($request->hasFile('image_file')) {
            if ($imagePath && !str_starts_with($imagePath, 'http')) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image_file')->store('app_banners', 'public');
        } elseif (!empty($validated['image_url'])) {
            $imagePath = trim($validated['image_url']);
        }

        $banner->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'badge' => $validated['badge'] ?: 'أشهر الواجهات',
            'image' => $imagePath,
            'link_type' => $validated['link_type'],
            'link_value' => $validated['link_value'] ?? null,
            'bg_gradient' => $validated['bg_gradient'] ?? $banner->bg_gradient,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'banners'])
            ->with('status', 'تم تحديث الإعلان والكلمات الإعلانية بنجاح.');
    }

    /**
     * Delete an app banner / ad.
     */
    public function destroyBanner(AppBanner $banner)
    {
        if ($banner->image && !str_starts_with($banner->image, 'http')) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return redirect()->route('admin.app-manager.index', ['tab' => 'banners'])
            ->with('status', 'تم حذف الإعلان بنجاح من تطبيق الجوال.');
    }

    /**
     * Toggle banner active status.
     */
    public function toggleBanner(AppBanner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'banners'])
            ->with('status', 'تم تغيير حالة ظهور الإعلان في التطبيق.');
    }

    /**
     * Store and dispatch a broadcast message to all users/subscribers without reply.
     */
    public function storeBroadcastMessage(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string', 'max:5000'],
            'target' => ['required', 'string', 'in:guests,everyone,staff'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'],
        ]);

        $sender = Auth::user();
        $senderId = $sender->id;

        $attachmentPath = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $attachmentPath = $request->file('attachment')->store('messages/attachments', 'public');
        }

        // Determine recipients
        $query = User::query();
        if ($validated['target'] === 'guests') {
            $query->where('role', 'guest');
        } elseif ($validated['target'] === 'staff') {
            $query->where('role', '!=', 'guest')->where('id', '!=', $senderId);
        } else {
            // everyone
            $query->where('id', '!=', $senderId);
        }

        $recipients = $query->get();

        $count = 0;
        foreach ($recipients as $recipient) {
            Message::create([
                'sender_id' => $senderId,
                'receiver_id' => $recipient->id,
                'hotel_id' => $sender->hotel_id ?? null,
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'attachment' => $attachmentPath,
                'sender_type' => 'system',
                'is_broadcast' => true,
                'allow_reply' => false,
                'is_read' => false,
            ]);

            // Notify user of new announcement message
            NotificationService::sendToUser(
                $recipient,
                '📢 تعميم جديد: ' . $validated['subject'],
                Str::limit($validated['body'], 120),
                'broadcast_message',
                '📢',
                $recipient->role === 'guest' ? route('guest.messages') : route('messages.inbox')
            );

            $count++;
        }

        AppBroadcast::create([
            'sender_id' => $senderId,
            'type' => 'message',
            'title' => $validated['subject'],
            'body' => $validated['body'],
            'target' => $validated['target'],
            'icon' => '📢',
            'allow_reply' => false,
            'recipients_count' => $count,
        ]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'broadcast-messages'])
            ->with('status', "تم إرسال الرسالة التعميمية بنجاح إلى {$count} مشترك مع إيقاف خاصية الرد.");
    }

    /**
     * Store and dispatch an event push notification to all users.
     */
    public function storeBroadcastNotification(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1000'],
            'event_type' => ['required', 'string', 'max:60'],
            'icon' => ['nullable', 'string', 'max:20'],
            'target' => ['required', 'string', 'in:guests,everyone,staff'],
            'link' => ['nullable', 'string', 'max:300'],
        ]);

        $sender = Auth::user();
        $senderId = $sender->id;

        // Determine recipients
        $query = User::query();
        if ($validated['target'] === 'guests') {
            $query->where('role', 'guest');
        } elseif ($validated['target'] === 'staff') {
            $query->where('role', '!=', 'guest')->where('id', '!=', $senderId);
        } else {
            $query->where('id', '!=', $senderId);
        }

        $recipients = $query->get();

        $icon = $validated['icon'] ?: '🔔';
        $eventType = $validated['event_type'] ?: 'event_notification';

        $count = 0;
        foreach ($recipients as $recipient) {
            NotificationService::sendToUser(
                $recipient,
                $validated['title'],
                $validated['body'],
                $eventType,
                $icon,
                $validated['link'] ?? null
            );
            $count++;
        }

        AppBroadcast::create([
            'sender_id' => $senderId,
            'type' => 'event_notification',
            'title' => $validated['title'],
            'body' => $validated['body'],
            'target' => $validated['target'],
            'event_type' => $eventType,
            'icon' => $icon,
            'link' => $validated['link'] ?? null,
            'allow_reply' => false,
            'recipients_count' => $count,
        ]);

        return redirect()->route('admin.app-manager.index', ['tab' => 'broadcast-events'])
            ->with('status', "تم إرسال الإشعار والتنبيه بالحدث بنجاح إلى {$count} مستخدم.");
    }

    /**
     * Delete a broadcast campaign record from log.
     */
    public function destroyBroadcast(AppBroadcast $broadcast)
    {
        $type = $broadcast->type;
        $broadcast->delete();

        $targetTab = $type === 'message' ? 'broadcast-messages' : 'broadcast-events';

        return redirect()->route('admin.app-manager.index', ['tab' => $targetTab])
            ->with('status', 'تم حذف سجل البث بنجاح.');
    }

    /**
     * Update the first-login/registration welcome notification settings.
     */
    public function updateWelcomeNotification(Request $request)
    {
        $validated = $request->validate([
            'app_welcome_enabled' => ['nullable'],
            'app_welcome_title' => ['required', 'string', 'max:255'],
            'app_welcome_message' => ['required', 'string', 'max:1000'],
        ]);

        SiteSetting::set('app_welcome_enabled', $request->has('app_welcome_enabled') ? '1' : '0', 'mobile_app');
        SiteSetting::set('app_welcome_title', $validated['app_welcome_title'], 'mobile_app');
        SiteSetting::set('app_welcome_message', $validated['app_welcome_message'], 'mobile_app');

        return redirect()->route('admin.app-manager.index', ['tab' => 'welcome-notification'])
            ->with('status', 'تم حفظ وتحديث إعدادات إشعار ورسالة الترحيب بنجاح.');
    }
}

