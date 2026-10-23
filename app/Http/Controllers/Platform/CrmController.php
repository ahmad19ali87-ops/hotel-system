<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\Hotel;
use App\Models\HotelSubscription;
use App\Models\MarketingCampaign;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmController extends Controller
{
    private function authorizePlatform(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['super_admin', 'platform_admin', 'platform_staff'], true)) {
            abort(403, 'غير مصرح لك بالوصول إلى نظام إدارة العملاء والاشتراكات (CRM). مخصص لإدارة المنصة فقط.');
        }
    }

    /**
     * Display the Central Integrated Platform CRM Dashboard.
     */
    public function index(Request $request)
    {
        $this->authorizePlatform();
        SubscriptionPlan::seedDefaultsIfNeeded();

        $tab = $request->get('tab', 'overview');

        // General Metrics & Reports
        $totalHotels = Hotel::count();
        $activeHotelsCount = Hotel::where('is_visible', true)->count();
        
        $plans = SubscriptionPlan::orderBy('sort_order')->get();
        $subscriptions = HotelSubscription::with(['hotel', 'plan'])->latest('id')->get();
        
        $activeSubscriptionsCount = HotelSubscription::whereIn('status', ['active', 'trial', 'grace_period'])
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()))
            ->count();

        $expiringSubscriptions = HotelSubscription::with(['hotel', 'plan'])
            ->whereIn('status', ['active', 'trial', 'grace_period'])
            ->whereBetween('expires_at', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->get();

        $expiredSubscriptionsCount = HotelSubscription::where('status', 'expired')
            ->orWhere('expires_at', '<', now()->toDateString())
            ->count();

        // Invoices Metrics
        $invoicesQuery = SubscriptionInvoice::with(['hotel', 'plan', 'subscription'])->latest('id');
        $invoices = (clone $invoicesQuery)->paginate(25, ['*'], 'invoices_page');
        
        $totalCollectedUSD = SubscriptionInvoice::where('status', 'paid')->where('currency', 'USD')->sum('amount');
        $totalCollectedSYP = SubscriptionInvoice::where('status', 'paid')->where('currency', 'SYP')->sum('amount');
        $unpaidInvoicesCount = SubscriptionInvoice::where('status', 'unpaid')->count();

        // Support Tickets
        $ticketsQuery = SupportTicket::with(['hotel', 'creator', 'assignedAgent', 'latestMessage'])->latest('updated_at');
        if ($request->filled('ticket_status')) {
            $ticketsQuery->where('status', $request->ticket_status);
        }
        if ($request->filled('ticket_priority')) {
            $ticketsQuery->where('priority', $request->ticket_priority);
        }
        $tickets = (clone $ticketsQuery)->paginate(20, ['*'], 'tickets_page');
        $openTicketsCount = SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
        $urgentTicketsCount = SupportTicket::where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count();

        // Leads & Sales Pipeline
        $leadsQuery = CrmLead::with(['assignedAgent', 'convertedHotel'])->latest('id');
        if ($request->filled('lead_status')) {
            $leadsQuery->where('status', $request->lead_status);
        }
        $leads = (clone $leadsQuery)->paginate(25, ['*'], 'leads_page');
        $activeLeadsCount = CrmLead::whereNotIn('status', ['won_converted', 'lost'])->count();
        $wonLeadsCount = CrmLead::where('status', 'won_converted')->count();

        // Team Members (Platform Admins & Staff)
        $team = User::whereIn('role', ['super_admin', 'platform_admin', 'platform_staff'])->orderBy('name')->get();

        // Marketing Campaigns
        $campaigns = MarketingCampaign::latest('id')->get();

        // Hotels with their current subscription & manager
        $hotelsWithSubs = Hotel::with(['currentSubscription.plan', 'rooms'])
            ->withCount(['rooms', 'bookings', 'guests'])
            ->latest('id')
            ->paginate(20, ['*'], 'hotels_page');

        // Calculate Monthly Recurring Revenue (MRR)
        $mrrUSD = 0.0;
        $mrrSYP = 0.0;
        foreach ($subscriptions as $sub) {
            if ($sub->status === 'active' && !$sub->is_expired && $sub->plan) {
                if ($sub->currency === 'USD') {
                    $mrrUSD += ($sub->billing_cycle === 'yearly') ? round($sub->plan->price_yearly_usd / 12, 2) : $sub->plan->price_monthly_usd;
                } else {
                    $mrrSYP += ($sub->billing_cycle === 'yearly') ? round($sub->plan->price_yearly_syp / 12, 2) : $sub->plan->price_monthly_syp;
                }
            }
        }
        $arrUSD = $mrrUSD * 12;
        $arrSYP = $mrrSYP * 12;

        return view('platform.crm.index', compact(
            'tab',
            'totalHotels',
            'activeHotelsCount',
            'plans',
            'subscriptions',
            'activeSubscriptionsCount',
            'expiringSubscriptions',
            'expiredSubscriptionsCount',
            'invoices',
            'totalCollectedUSD',
            'totalCollectedSYP',
            'unpaidInvoicesCount',
            'tickets',
            'openTicketsCount',
            'urgentTicketsCount',
            'leads',
            'activeLeadsCount',
            'wonLeadsCount',
            'team',
            'campaigns',
            'hotelsWithSubs',
            'mrrUSD',
            'mrrSYP',
            'arrUSD',
            'arrSYP'
        ));
    }

    /**
     * Store or update a subscription plan.
     */
    public function storePlan(Request $request)
    {
        $this->authorizePlatform();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'price_monthly_syp' => 'required|numeric|min:0',
            'price_yearly_syp' => 'required|numeric|min:0',
            'price_monthly_usd' => 'required|numeric|min:0',
            'price_yearly_usd' => 'required|numeric|min:0',
            'max_rooms' => 'required|integer',
            'max_users' => 'required|integer',
            'badge' => 'nullable|string|max:50',
            'features' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $featuresList = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $validated['features'] ?? ''))));
        $slug = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        if (empty($slug)) {
            $slug = 'plan-' . uniqid();
        }

        SubscriptionPlan::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'price_monthly_syp' => $validated['price_monthly_syp'],
            'price_yearly_syp' => $validated['price_yearly_syp'],
            'price_monthly_usd' => $validated['price_monthly_usd'],
            'price_yearly_usd' => $validated['price_yearly_usd'],
            'max_rooms' => $validated['max_rooms'],
            'max_users' => $validated['max_users'],
            'features' => json_encode(array_values($featuresList), JSON_UNESCAPED_UNICODE),
            'badge' => $validated['badge'] ?? null,
            'is_active' => $request->has('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('platform.crm.index', ['tab' => 'subscriptions'])->with('success', 'تم إنشاء باقة الاشتراك الجديدة بنجاح.');
    }

    /**
     * Assign or renew a hotel subscription.
     */
    public function assignSubscription(Request $request)
    {
        $this->authorizePlatform();

        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'currency' => 'nullable|in:USD,SYP',
            'duration_months' => 'required|integer|min:1|max:120',
            'price_paid' => 'required|numeric|min:0',
            'status' => 'required|in:active,trial,grace_period',
            'create_invoice' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $hotel = Hotel::findOrFail($validated['hotel_id']);
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        
        $startsAt = Carbon::today();
        $expiresAt = ($validated['billing_cycle'] === 'lifetime')
            ? Carbon::today()->addYears(20)
            : Carbon::today()->addMonths((int)$validated['duration_months']);

        $sub = HotelSubscription::create([
            'hotel_id' => $hotel->id,
            'plan_id' => $plan->id,
            'status' => $validated['status'],
            'billing_cycle' => $validated['billing_cycle'],
            'currency' => $validated['currency'] ?? 'USD',
            'price_paid' => $validated['price_paid'],
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'notes' => $validated['notes'] ?? 'تم تعيين الاشتراك من لوحة إدارة المنصة.',
        ]);

        if ($request->has('create_invoice') && (float)$validated['price_paid'] > 0) {
            SubscriptionInvoice::create([
                'hotel_id' => $hotel->id,
                'subscription_id' => $sub->id,
                'plan_id' => $plan->id,
                'amount' => $validated['price_paid'],
                'currency' => $validated['currency'],
                'status' => 'paid',
                'payment_method' => $request->input('payment_method', 'bank_transfer'),
                'paid_at' => now(),
                'due_date' => now()->toDateString(),
                'notes' => "فاتورة سداد اشتراك {$plan->name} ({$validated['billing_cycle']})",
            ]);
        }

        return redirect()->route('platform.crm.index', ['tab' => 'hotels'])->with('success', "تم تفعيل وتعيين اشتراك {$plan->name} للفندق {$hotel->name} بنجاح.");
    }

    /**
     * Create a new subscription invoice.
     */
    public function storeInvoice(Request $request)
    {
        $this->authorizePlatform();

        $validated = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,SYP',
            'status' => 'required|in:paid,unpaid',
            'payment_method' => 'required|string|max:50',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $hotel = Hotel::findOrFail($validated['hotel_id']);
        $sub = $hotel->active_subscription;

        SubscriptionInvoice::create([
            'hotel_id' => $hotel->id,
            'subscription_id' => $sub?->id,
            'plan_id' => $validated['plan_id'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'status' => $validated['status'],
            'payment_method' => $validated['payment_method'],
            'paid_at' => ($validated['status'] === 'paid') ? now() : null,
            'due_date' => $validated['due_date'] ?? now()->addDays(14)->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('platform.crm.index', ['tab' => 'invoices'])->with('success', 'تم إصدار الفاتورة وحفظها بنجاح.');
    }

    /**
     * Mark an invoice as paid.
     */
    public function markInvoicePaid(SubscriptionInvoice $invoice)
    {
        $this->authorizePlatform();

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('success', "تم تحصيل الفاتورة {$invoice->invoice_number} وتأكيد السداد بنجاح.");
    }

    /**
     * Show single ticket details & reply thread in platform CRM.
     */
    public function showTicket(SupportTicket $ticket)
    {
        $this->authorizePlatform();

        $ticket->load(['hotel', 'creator', 'assignedAgent', 'messages.sender']);
        $team = User::whereIn('role', ['super_admin', 'platform_admin', 'platform_staff'])->get();

        return view('platform.crm.ticket_detail', compact('ticket', 'team'));
    }

    /**
     * Reply to a support ticket as platform staff.
     */
    public function replyTicket(Request $request, SupportTicket $ticket)
    {
        $this->authorizePlatform();

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'status' => 'nullable|in:open,in_progress,waiting_customer,answered,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_staff_reply' => true,
        ]);

        $updates = ['last_reply_at' => now()];
        if (!empty($validated['status'])) {
            $updates['status'] = $validated['status'];
        } else {
            $updates['status'] = 'waiting_customer';
        }

        if ($request->has('assigned_to')) {
            $updates['assigned_to'] = $validated['assigned_to'];
        }

        $ticket->update($updates);

        return back()->with('success', 'تم إرسال رد الدعم الفني وتحديث حالة التذكرة بنجاح.');
    }

    /**
     * Store new CRM Lead (Sales Pipeline).
     */
    public function storeLead(Request $request)
    {
        $this->authorizePlatform();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hotel_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'source' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'expected_revenue' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:USD,SYP',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        CrmLead::create([
            'name' => $validated['name'],
            'hotel_name' => $validated['hotel_name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'province' => $validated['province'] ?? null,
            'city' => $validated['city'] ?? null,
            'source' => $validated['source'],
            'status' => $validated['status'],
            'expected_revenue' => $validated['expected_revenue'] ?? 0,
            'currency' => $validated['currency'] ?? 'USD',
            'assigned_to' => $validated['assigned_to'] ?? auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('platform.crm.index', ['tab' => 'sales'])->with('success', 'تمت إضافة العميل المحتمل إلى مسار المبيعات بنجاح.');
    }

    /**
     * Update CRM Lead Status.
     */
    public function updateLeadStatus(Request $request, CrmLead $lead)
    {
        $this->authorizePlatform();

        $validated = $request->validate([
            'status' => 'required|in:new,contacted,demo_scheduled,proposal_sent,won_converted,lost',
            'notes' => 'nullable|string|max:2000',
        ]);

        $lead->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ? ($lead->notes . "\n[" . now()->format('Y-m-d H:i') . "] " . $validated['notes']) : $lead->notes,
        ]);

        return back()->with('success', 'تم تحديث مرحلة العميل المحتمل بنجاح.');
    }

    /**
     * Store Marketing Campaign / Coupon Code.
     */
    public function storeCampaign(Request $request)
    {
        $this->authorizePlatform();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:marketing_campaigns,code',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_fixed' => 'nullable|numeric|min:0',
            'currency' => 'required|in:USD,SYP',
            'type' => 'required|string|max:50',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'max_uses' => 'nullable|integer',
            'description' => 'nullable|string|max:1000',
        ]);

        MarketingCampaign::create([
            'title' => $validated['title'],
            'code' => strtoupper(trim($validated['code'])),
            'discount_percent' => $validated['discount_percent'] ?? 0,
            'discount_fixed' => $validated['discount_fixed'] ?? 0,
            'currency' => $validated['currency'],
            'type' => $validated['type'],
            'starts_at' => $validated['starts_at'] ?? now()->toDateString(),
            'ends_at' => $validated['ends_at'] ?? null,
            'max_uses' => $validated['max_uses'] ?? -1,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('platform.crm.index', ['tab' => 'marketing'])->with('success', 'تم إطلاق الحملة الترويجية وكود الخصم بنجاح.');
    }

    /**
     * Toggle Marketing Campaign status.
     */
    public function toggleCampaign(MarketingCampaign $campaign)
    {
        $this->authorizePlatform();

        $campaign->update(['is_active' => !$campaign->is_active]);

        return back()->with('success', 'تم تغيير حالة الحملة الترويجية بنجاح.');
    }
}

