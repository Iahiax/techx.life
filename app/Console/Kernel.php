<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * تعريف أوامر Artisan المخصصة.
     *
     * @var array
     */
    protected $commands = [
        // تسجيل الأمر المخصص لمعالجة الاستقطاعات
        \App\Console\Commands\ProcessDeductions::class,
    ];

    /**
     * جدولة الأوامر.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // تنفيذ الأمر الخاص بالاستقطاعات يومياً في الساعة 2 صباحاً
        $schedule->command('deductions:process')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/deductions.log'));

        // يمكن إضافة أوامر أخرى هنا مثل تنظيف الجلسات أو الإشعارات
        // $schedule->command('model:prune')->daily();
    }

    /**
     * تسجيل الأوامر المخصصة.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
