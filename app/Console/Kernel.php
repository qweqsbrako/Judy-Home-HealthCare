<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */

    protected function schedule(Schedule $schedule): void
    {
        // Reminder #1: Every morning at 8 AM for tomorrow's schedules
        $schedule->command('schedules:send-reminders --type=daily --max-reminders=3')
            ->dailyAt('08:00')
            ->timezone('Africa/Accra')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/schedule-reminders.log'));

        // Reminder #2: Every 30 minutes for schedules starting in next 2 hours
        $schedule->command('schedules:send-reminders --type=urgent --max-reminders=3')
            ->everyThirtyMinutes()
            ->timezone('Africa/Accra')
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}