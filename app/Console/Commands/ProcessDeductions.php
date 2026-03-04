// app/Console/Commands/ProcessDeductions.php
public function handle()
{
    $today = now()->toDateString();
    $deductions = Deduction::where('scheduled_date', $today)
                            ->where('status', 'pending')
                            ->get();
    foreach ($deductions as $deduction) {
        // تنفيذ الاستقطاع عبر Open Banking
        $engine = new DeductionEngine();
        $engine->process($deduction);
    }
}
