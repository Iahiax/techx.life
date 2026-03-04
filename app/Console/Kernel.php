// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('deductions:process')->dailyAt('02:00');
}
