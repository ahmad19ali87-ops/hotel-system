<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ReportController;

class DailySnapshotCommand extends Command
{
    protected $signature = 'snapshot:daily';
    protected $description = 'Generate daily hotel snapshot report';

    public function handle()
    {
        (new ReportController)->dailySnapshot();
        $this->info('Daily snapshot generated successfully.');
    }
}
