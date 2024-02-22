<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CheckOrdersExpiration;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected $commands = [
        // Daftarkan command artisan yang diperlukan di sini
        CheckOrdersExpiration::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('check:orders-expiration');
        $schedule->call(function () {
            Log::info('Cronjob berhasil dijalankan');
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
