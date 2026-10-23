<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelSubscriptionController extends Controller
{
    private function hotelId(): int
    {
        return (int) Auth::user()->hotel_id;
    }

    /**
     * Display current hotel subscription, usage, available plans, and past invoices.
     */
    public function index()
    {
        $hotel = Hotel::with(['rooms', 'rooms.roomType'])->findOrFail($this->hotelId());
        SubscriptionPlan::seedDefaultsIfNeeded();

        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        
        // Ensure hotel has at least a trial or standard subscription record
        $subscription = $hotel->active_subscription;
        if (!$subscription) {
            $defaultPlan = SubscriptionPlan::where('slug', 'pro')->first() ?? $plans->first();
            if ($defaultPlan) {
                $subscription = HotelSubscription::create([
                    'hotel_id' => $hotel->id,
                    'plan_id' => $defaultPlan->id,
                    'status' => 'active',
                    'billing_cycle' => 'monthly',
                    'currency' => 'USD',
                    'price_paid' => $defaultPlan->price_monthly_usd,
                    'starts_at' => now()->toDateString(),
                    'expires_at' => now()->addMonths(1)->toDateString(),
                    'notes' => 'اشتراك تلقائي مفعل من النظام',
                ]);
            }
        }

        $invoices = SubscriptionInvoice::where('hotel_id', $hotel->id)
            ->with('plan')
            ->latest('id')
            ->get();

        $roomsCount = $hotel->rooms()->count();
        $maxRooms = $subscription?->plan?->max_rooms ?? -1;

        return view('admin.subscription.index', compact('hotel', 'subscription', 'plans', 'invoices', 'roomsCount', 'maxRooms'));
    }

    /**
     * Request a renewal or plan upgrade (Creates a billing support ticket and pending invoice).
     */
    public function requestPlan(Request $request)
    {
        $hotel = Hotel::findOrFail($this->hotelId());
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'currency' => 'nullable|in:USD,SYP',
            'notes' => 'nullable|string|max:1000',
        ]);

        $currency = $validated['currency'] ?? 'USD';
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $amount = ($currency === 'USD')
            ? (($validated['billing_cycle'] === 'yearly') ? $plan->price_yearly_usd : $plan->price_monthly_usd)
            : (($validated['billing_cycle'] === 'yearly') ? $plan->price_yearly_syp : $plan->price_monthly_syp);

        // 1. Create a pending invoice
        $inv = SubscriptionInvoice::create([
            'hotel_id' => $hotel->id,
            'plan_id' => $plan->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'unpaid',
            'payment_method' => 'bank_transfer',
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => "طلب اشتراك في {$plan->name} ({$validated['billing_cycle']}) من قبل إدارة الفندق.",
        ]);

        // 2. Create support ticket for platform team
        $ticket = SupportTicket::create([
            'hotel_id' => $hotel->id,
            'user_id' => auth()->id(),
            'subject' => "طلب ترقية / تجديد اشتراك: {$plan->name}",
            'department' => 'billing',
            'priority' => 'high',
            'status' => 'open',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => "مرحباً إدارة المنصة، نرغب في تجديد/ترقية اشتراك فندقنا ({$hotel->name}) إلى {$plan->name} بدورة سداد {$validated['billing_cycle']} بمبلغ {$amount} {$currency}. تم تسجيل الفاتورة رقم {$inv->invoice_number}.\nملاحظات: " . ($validated['notes'] ?? 'لا يوجد.'),
            'is_staff_reply' => false,
        ]);

        return redirect()->route('admin.subscription.index')->with('success', "تم تسجيل طلب الاشتراك في {$plan->name} وإصدار الفاتورة المبدئية رقم {$inv->invoice_number}. سيتواصل معكم فريق المنصة لإتمام التأكيد والتفعيل.");
    }
}

