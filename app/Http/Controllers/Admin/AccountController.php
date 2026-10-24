<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\DeletionLog;
use Carbon\Carbon;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class AccountController extends Controller
{
    public function index()
    {
        $hotelId = auth()->user()->hotel_id;
        $accounts = Account::where('hotel_id', $hotelId)->with('invoice')->latest('transaction_date')->latest('id')->get();
        $invoices = Invoice::where('hotel_id', $hotelId)
            ->with(['booking.guest', 'room', 'payments'])
            ->latest()
            ->get();
        $incomeSYP = Account::where('hotel_id', $hotelId)->where('currency', 'SYP')->where('type', 'income')->sum('amount');
        $expensesSYP = Account::where('hotel_id', $hotelId)->where('currency', 'SYP')->where('type', 'expense')->sum('amount');
        $incomeUSD = Account::where('hotel_id', $hotelId)->where('currency', 'USD')->where('type', 'income')->sum('amount');
        $expensesUSD = Account::where('hotel_id', $hotelId)->where('currency', 'USD')->where('type', 'expense')->sum('amount');
        $income = Account::where('hotel_id', $hotelId)->where('type', 'income')->sum('amount');
        $expenses = Account::where('hotel_id', $hotelId)->where('type', 'expense')->sum('amount');

        return view('admin.accounts.index', compact('accounts', 'invoices', 'income', 'expenses', 'incomeSYP', 'expensesSYP', 'incomeUSD', 'expensesUSD'));
    }

    public function reports(Request $request)
    {
        $data = $this->reportData($request);

        return view('admin.accounts.reports', $data);
    }

    public function reportsPdf(Request $request)
    {
        $data = $this->reportData($request);
        $html = view('admin.accounts.report-pdf', $data)->render();
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'directionality' => 'rtl',
            'default_font' => 'dejavusans',
            'tempDir' => storage_path('app/mpdf-temp'),
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->SetDirectionality('rtl');
        $pdf->SetTitle('كشف الحساب المالي - ' . $data['hotel']->name);
        $pdf->WriteHTML($html);

        return response($pdf->Output('financial-statement-' . $data['from'] . '-to-' . $data['to'] . '.pdf', Destination::STRING_RETURN))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="financial-statement.pdf"');
    }

    public function create()
    {
        return view('admin.accounts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'title' => 'required',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|in:SYP,USD',
            'transaction_date' => 'required|date',
            'notes' => 'nullable'
        ]);

        Account::create([
            'hotel_id' => auth()->user()->hotel_id,
            'type' => $request->type,
            'title' => $request->title,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'SYP',
            'transaction_date' => $request->transaction_date,
            'notes' => $request->notes
        ]);

        return redirect()->route('accounts.index')->with('success', 'تم تسجيل العملية المالية بنجاح');
    }

    public function edit(Account $account)
    {
        abort_unless((int) $account->hotel_id === (int) auth()->user()->hotel_id, 404);
        return view('admin.accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        abort_unless((int) $account->hotel_id === (int) auth()->user()->hotel_id && ! $account->invoice_id, 404);
        $request->validate([
            'type' => 'required|in:income,expense',
            'title' => 'required',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|in:SYP,USD',
            'transaction_date' => 'required|date',
            'notes' => 'nullable'
        ]);

        $account->update([
            'type' => $request->type,
            'title' => $request->title,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'SYP',
            'transaction_date' => $request->transaction_date,
            'notes' => $request->notes
        ]);

        return redirect()->route('accounts.index')->with('success', 'تم تعديل العملية المالية بنجاح');
    }

    public function destroy(Request $request, Account $account)
    {
        abort_unless((int) $account->hotel_id === (int) auth()->user()->hotel_id, 404);

        if ($account->invoice_id) {
            return redirect()
                ->route('accounts.index')
                ->with('warning', 'لا يمكن حذف قيد محاسبي مرتبط بفاتورة حجز.');
        }

        DeletionLog::create([
            'hotel_id' => $account->hotel_id,
            'user_id' => auth()->id(),
            'record_type' => 'account',
            'record_id' => $account->id,
            'reference_number' => $account->reference_number ?: ('قيد #' . $account->id),
            'guest_name' => '—',
            'room_number' => '—',
            'amount' => $account->amount,
            'currency' => $account->currency ?: 'USD',
            'payment_status' => $account->type,
            'reason' => $request->input('reason', 'حذف يدوي لقيد في دفتر اليومية من لوحة الإدارة'),
            'snapshot' => $account->toArray(),
            'deleted_at' => now(),
        ]);

        $account->delete();

        return redirect()
            ->route('accounts.index')
            ->with('success', 'تم حذف القيد المحاسبي وتوثيقه في سجل المحذوفات.');
    }

    private function reportData(Request $request): array
    {
        $period = $request->query('period', 'monthly');
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly', 'custom'])) {
            $period = 'monthly';
        }

        $now = Carbon::now();
        $date = $request->query('date', $now->toDateString());
        $weekDate = $request->query('week_date', $now->toDateString());
        $month = $request->query('month', $now->format('Y-m'));
        $year = (int)$request->query('year', $now->year);
        $currency = $request->query('currency', 'all');
        if (!in_array($currency, ['all', 'SYP', 'USD'])) {
            $currency = 'all';
        }
        $type = $request->query('type', 'all');
        if (!in_array($type, ['all', 'income', 'expense'])) {
            $type = 'all';
        }

        switch ($period) {
            case 'daily':
                $targetDate = Carbon::parse($date)->toDateString();
                $from = $targetDate;
                $to = $targetDate;
                $periodLabel = 'تقرير يومي ليوم ' . $targetDate;
                break;

            case 'weekly':
                $carbonWeek = Carbon::parse($weekDate);
                $from = $carbonWeek->copy()->startOfWeek()->toDateString();
                $to = $carbonWeek->copy()->endOfWeek()->toDateString();
                $periodLabel = 'تقرير أسبوعي للفترة من ' . $from . ' إلى ' . $to;
                break;

            case 'yearly':
                $from = Carbon::createFromDate($year, 1, 1)->startOfYear()->toDateString();
                $to = Carbon::createFromDate($year, 12, 31)->endOfYear()->toDateString();
                $periodLabel = 'تقرير سنوي لعام ' . $year;
                break;

            case 'custom':
                $from = $request->query('from', $now->copy()->startOfMonth()->toDateString());
                $to = $request->query('to', $now->toDateString());
                if ($from > $to) {
                    $from = $to;
                }
                $periodLabel = 'تقرير مخصص للفترة من ' . $from . ' إلى ' . $to;
                break;

            case 'monthly':
            default:
                try {
                    $carbonMonth = Carbon::parse($month . '-01');
                } catch (\Exception $e) {
                    $carbonMonth = $now;
                }
                $from = $carbonMonth->copy()->startOfMonth()->toDateString();
                $to = $carbonMonth->copy()->endOfMonth()->toDateString();
                $periodLabel = 'تقرير شهري لشهر ' . $carbonMonth->format('m/Y');
                break;
        }

        $hotel = auth()->user()->hotel;

        // Opening Balance: Transactions before $from for this hotel
        $priorQuery = Account::where('hotel_id', $hotel->id)
            ->whereDate('transaction_date', '<', $from);

        if ($currency === 'USD') {
            $priorIncomeUSD = (clone $priorQuery)->where('currency', 'USD')->where('type', 'income')->sum('amount');
            $priorExpenseUSD = (clone $priorQuery)->where('currency', 'USD')->where('type', 'expense')->sum('amount');
            $openingBalanceUSD = (float)($priorIncomeUSD - $priorExpenseUSD);
            $openingBalanceSYP = 0.0;
        } elseif ($currency === 'SYP') {
            $priorIncomeSYP = (clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'income')->sum('amount');
            $priorExpenseSYP = (clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'expense')->sum('amount');
            $openingBalanceSYP = (float)($priorIncomeSYP - $priorExpenseSYP);
            $openingBalanceUSD = 0.0;
        } else {
            $priorIncomeSYP = (clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'income')->sum('amount');
            $priorExpenseSYP = (clone $priorQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'expense')->sum('amount');
            $openingBalanceSYP = (float)($priorIncomeSYP - $priorExpenseSYP);

            $priorIncomeUSD = (clone $priorQuery)->where('currency', 'USD')->where('type', 'income')->sum('amount');
            $priorExpenseUSD = (clone $priorQuery)->where('currency', 'USD')->where('type', 'expense')->sum('amount');
            $openingBalanceUSD = (float)($priorIncomeUSD - $priorExpenseUSD);
        }

        $openingBalance = $currency === 'USD' ? $openingBalanceUSD : $openingBalanceSYP;

        // Transactions within period
        $periodQuery = Account::where('hotel_id', $hotel->id)
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to);

        if ($currency === 'USD') {
            $periodQuery->where('currency', 'USD');
        } elseif ($currency === 'SYP') {
            $periodQuery->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'));
        }

        // Breakdown by currency during period
        $incomeSYP = (clone $periodQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'income')->sum('amount');
        $expenseSYP = (clone $periodQuery)->where(fn($q) => $q->where('currency', 'SYP')->orWhereNull('currency'))->where('type', 'expense')->sum('amount');
        $incomeUSD = (clone $periodQuery)->where('currency', 'USD')->where('type', 'income')->sum('amount');
        $expenseUSD = (clone $periodQuery)->where('currency', 'USD')->where('type', 'expense')->sum('amount');

        $totalIncome = (clone $periodQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $periodQuery)->where('type', 'expense')->sum('amount');

        // Entries
        $entries = (clone $periodQuery)
            ->when($type !== 'all', fn ($q) => $q->where('type', $type))
            ->with('invoice')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Build bank statement rows with separate running balances
        $runningBalanceSYP = $openingBalanceSYP;
        $runningBalanceUSD = $openingBalanceUSD;
        $totalInflowSYP = 0.0;
        $totalOutflowSYP = 0.0;
        $totalInflowUSD = 0.0;
        $totalOutflowUSD = 0.0;
        $statementRows = [];

        foreach ($entries as $entry) {
            $entryCurrency = ($entry->currency === 'USD') ? 'USD' : 'SYP';
            $isIncome = ($entry->type === 'income');
            $inflow = $isIncome ? (float)$entry->amount : 0.0;
            $outflow = !$isIncome ? (float)$entry->amount : 0.0;

            if ($entryCurrency === 'USD') {
                $totalInflowUSD += $inflow;
                $totalOutflowUSD += $outflow;
                $runningBalanceUSD += ($inflow - $outflow);
            } else {
                $totalInflowSYP += $inflow;
                $totalOutflowSYP += $outflow;
                $runningBalanceSYP += ($inflow - $outflow);
            }

            $statementRows[] = [
                'id' => $entry->id,
                'date' => $entry->transaction_date,
                'ref' => $entry->invoice?->invoice_number ?? ('TXN-' . str_pad($entry->id, 5, '0', STR_PAD_LEFT)),
                'title' => $entry->title,
                'notes' => $entry->notes,
                'type' => $entry->type,
                'currency' => $entryCurrency,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'balance' => $entryCurrency === 'USD' ? $runningBalanceUSD : $runningBalanceSYP,
                'balance_syp' => $runningBalanceSYP,
                'balance_usd' => $runningBalanceUSD,
                'invoice' => $entry->invoice,
            ];
        }

        $closingBalanceSYP = $runningBalanceSYP;
        $closingBalanceUSD = $runningBalanceUSD;

        $totalInflow = $currency === 'USD' ? $totalInflowUSD : $totalInflowSYP;
        $totalOutflow = $currency === 'USD' ? $totalOutflowUSD : $totalOutflowSYP;
        $closingBalance = $currency === 'USD' ? $closingBalanceUSD : $closingBalanceSYP;

        return [
            'hotel' => $hotel,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'date' => $date,
            'weekDate' => $weekDate,
            'month' => $month,
            'year' => $year,
            'from' => $from,
            'to' => $to,
            'currency' => $currency,
            'type' => $type,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'netChange' => $totalInflow - $totalOutflow,
            'openingBalanceSYP' => $openingBalanceSYP,
            'openingBalanceUSD' => $openingBalanceUSD,
            'closingBalanceSYP' => $closingBalanceSYP,
            'closingBalanceUSD' => $closingBalanceUSD,
            'totalInflowSYP' => $totalInflowSYP,
            'totalOutflowSYP' => $totalOutflowSYP,
            'totalInflowUSD' => $totalInflowUSD,
            'totalOutflowUSD' => $totalOutflowUSD,
            'netChangeSYP' => $totalInflowSYP - $totalOutflowSYP,
            'netChangeUSD' => $totalInflowUSD - $totalOutflowUSD,
            'statementRows' => $statementRows,
            'entries' => $entries,
            'income' => $totalIncome,
            'expenses' => $totalExpense,
            'incomeSYP' => $incomeSYP,
            'expenseSYP' => $expenseSYP,
            'expensesSYP' => $expenseSYP,
            'incomeUSD' => $incomeUSD,
            'expenseUSD' => $expenseUSD,
            'expensesUSD' => $expenseUSD,
        ];
    }
}
