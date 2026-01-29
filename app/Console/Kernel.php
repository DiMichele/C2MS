<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\ImportPresenze::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Pulizia automatica log di audit (ogni mese, il primo giorno alle 2:00)
        // Mantiene solo gli ultimi X giorni (configurabile in config/audit.php) nel database
        // I log più vecchi vengono archiviati in CSV prima di essere eliminati
        $retentionDays = config('audit.retention_days', 365);
        $archive = config('audit.archive_before_delete', true) ? 'true' : 'false';
        
        $schedule->command("audit:clean --days={$retentionDays} --archive={$archive} --force")
            ->monthlyOn(1, '02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/audit-cleanup.log'));
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
