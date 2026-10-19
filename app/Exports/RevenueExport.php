<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RevenueExport implements FromView
{
    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function view(): View
    {
        $query = Invoice::where('status', 'paid');

        if ($this->from && $this->to) {
            $query->whereBetween('created_at', [$this->from, $this->to]);
        }

        return view('exports.revenue', [
            'invoices' => $query->get()
        ]);
    }
}
