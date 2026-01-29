<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Comando per visualizzare statistiche sui log di audit.
 * 
 * Uso:
 *   php artisan audit:stats
 */
class AuditLogStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostra statistiche sui log di audit nel database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  Statistiche Log di Audit');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Statistiche generali
        $totalLogs = AuditLog::count();
        $oldestLog = AuditLog::orderBy('created_at')->first();
        $newestLog = AuditLog::orderBy('created_at', 'desc')->first();

        $this->info('📊 Statistiche Generali:');
        $this->line("   • Totale log nel database: " . number_format($totalLogs));
        
        if ($oldestLog && $newestLog) {
            $this->line("   • Log più vecchio: " . $oldestLog->created_at->format('d/m/Y H:i:s'));
            $this->line("   • Log più recente: " . $newestLog->created_at->format('d/m/Y H:i:s'));
            
            $daysDiff = $oldestLog->created_at->diffInDays($newestLog->created_at);
            $this->line("   • Periodo coperto: {$daysDiff} giorni");
        }

        $this->newLine();

        // Statistiche per tipo di azione
        $this->info('📈 Log per Tipo di Azione:');
        $actions = AuditLog::select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        $actionData = [];
        foreach ($actions as $action) {
            $actionData[] = [
                $action->action,
                AuditLog::ACTION_LABELS[$action->action] ?? $action->action,
                number_format($action->count)
            ];
        }

        $this->table(['Codice', 'Azione', 'Conteggio'], $actionData);
        $this->newLine();

        // Statistiche per stato
        $this->info('📊 Log per Stato:');
        $statuses = AuditLog::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        $statusData = [];
        foreach ($statuses as $status) {
            $statusLabel = match($status->status) {
                'success' => 'Successo',
                'failed' => 'Fallito',
                'warning' => 'Attenzione',
                default => ucfirst($status->status)
            };
            $statusData[] = [
                $statusLabel,
                number_format($status->count),
                number_format(($status->count / $totalLogs) * 100, 2) . '%'
            ];
        }

        $this->table(['Stato', 'Conteggio', 'Percentuale'], $statusData);
        $this->newLine();

        // Statistiche per periodo
        $this->info('📅 Log per Periodo:');
        $periods = [
            ['Oggi', Carbon::today(), Carbon::now()],
            ['Ultima settimana', Carbon::now()->subWeek(), Carbon::now()],
            ['Ultimo mese', Carbon::now()->subMonth(), Carbon::now()],
            ['Ultimi 3 mesi', Carbon::now()->subMonths(3), Carbon::now()],
            ['Ultimo anno', Carbon::now()->subYear(), Carbon::now()],
        ];

        $periodData = [];
        foreach ($periods as [$label, $from, $to]) {
            $count = AuditLog::whereBetween('created_at', [$from, $to])->count();
            $periodData[] = [
                $label,
                number_format($count)
            ];
        }

        $this->table(['Periodo', 'Conteggio'], $periodData);
        $this->newLine();

        // Dimensione stimata del database
        $this->info('💾 Dimensione Database:');
        $avgLogSize = 500; // bytes per log (stima)
        $estimatedSize = $totalLogs * $avgLogSize;
        
        $sizeFormatted = $this->formatBytes($estimatedSize);
        $this->line("   • Dimensione stimata tabella audit_logs: {$sizeFormatted}");
        
        // Retention policy
        $retentionDays = config('audit.retention_days', 365);
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $logsToDelete = AuditLog::where('created_at', '<', $cutoffDate)->count();
        
        $this->newLine();
        $this->info('⚙️  Retention Policy:');
        $this->line("   • Retention configurata: {$retentionDays} giorni");
        $this->line("   • Data cutoff: {$cutoffDate->format('d/m/Y')}");
        $this->line("   • Log che verrebbero eliminati: " . number_format($logsToDelete));
        
        if ($logsToDelete > 0) {
            $spaceToFree = $this->formatBytes($logsToDelete * $avgLogSize);
            $this->line("   • Spazio che verrebbe liberato: {$spaceToFree}");
        }

        $this->newLine();
        $this->info('💡 Suggerimenti:');
        if ($logsToDelete > 0) {
            $this->line("   • Esegui 'php artisan audit:clean --dry-run' per vedere cosa verrebbe fatto");
        }
        if ($totalLogs > 100000) {
            $this->warn("   ⚠️  Hai più di 100,000 log. Considera di eseguire la pulizia.");
        }
        $this->line("   • I log vengono puliti automaticamente ogni mese (primo giorno alle 2:00)");

        return Command::SUCCESS;
    }

    /**
     * Formatta bytes in formato leggibile.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }
}
