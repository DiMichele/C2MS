<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Comando per pulire e archiviare i log di audit vecchi.
 * 
 * Questo comando:
 * 1. Esporta i log vecchi in CSV prima di eliminarli (archiviazione)
 * 2. Elimina i log più vecchi di X giorni (configurabile)
 * 3. Mantiene solo i log recenti nel database per performance
 * 
 * Uso:
 *   php artisan audit:clean --days=365 --archive=true
 *   php artisan audit:clean --days=730 --archive=true --dry-run
 */
class CleanOldAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:clean 
                            {--days=365 : Numero di giorni di retention (default: 365 = 1 anno)}
                            {--archive=true : Archivia i log in CSV prima di eliminarli (default: true)}
                            {--dry-run : Mostra cosa verrebbe fatto senza eseguire effettivamente}
                            {--force : Esegue senza chiedere conferma}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulisce e archivia i log di audit vecchi per mantenere le performance del database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Usa configurazione se non specificato
        $days = (int) ($this->option('days') ?: config('audit.retention_days', 365));
        $archive = $this->option('archive') !== null 
            ? filter_var($this->option('archive'), FILTER_VALIDATE_BOOLEAN)
            : config('audit.archive_before_delete', true);
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  Pulizia e Archiviazione Log di Audit');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Calcola la data di cutoff
        $cutoffDate = Carbon::now()->subDays($days);
        
        // Conta i log da eliminare
        $logsToDelete = AuditLog::where('created_at', '<', $cutoffDate)->count();
        
        if ($logsToDelete === 0) {
            $this->info("✓ Nessun log da eliminare (tutti i log sono più recenti di {$days} giorni)");
            return Command::SUCCESS;
        }

        $this->info("📊 Statistiche:");
        $this->line("   • Retention: {$days} giorni");
        $this->line("   • Data cutoff: {$cutoffDate->format('d/m/Y H:i:s')}");
        $this->line("   • Log da eliminare: " . number_format($logsToDelete));
        $this->line("   • Archiviazione: " . ($archive ? 'Sì' : 'No'));
        $this->line("   • Modalità: " . ($dryRun ? 'DRY RUN (simulazione)' : 'ESECUZIONE'));
        $this->newLine();

        if ($dryRun) {
            $this->warn("⚠️  MODALITÀ DRY RUN - Nessuna modifica verrà effettuata");
            $this->newLine();
            return Command::SUCCESS;
        }

        // Conferma se non è forzato
        if (!$force && !$this->confirm("Vuoi procedere con l'eliminazione di {$logsToDelete} log?", true)) {
            $this->info('Operazione annullata.');
            return Command::FAILURE;
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($logsToDelete);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('Inizio operazione...');
        $bar->start();

        try {
            // STEP 1: Archiviazione (se richiesta)
            if ($archive) {
                $bar->setMessage('Archiviazione log in corso...');
                $archiveFile = $this->archiveLogs($cutoffDate);
                
                if ($archiveFile) {
                    $bar->setMessage("Archiviati in: {$archiveFile}");
                    $this->newLine();
                    $this->info("✓ Archiviazione completata: {$archiveFile}");
                } else {
                    $this->newLine();
                    $this->warn("⚠️  Archiviazione fallita, ma procedo con l'eliminazione");
                }
                $bar->setMessage('Eliminazione log in corso...');
            }

            // STEP 2: Eliminazione
            // Elimina in batch per evitare timeout su grandi quantità
            $deleted = 0;
            $batchSize = config('audit.performance.delete_batch_size', 1000);
            $batchDelay = config('audit.performance.batch_delay', 100000);
            
            do {
                $batch = AuditLog::where('created_at', '<', $cutoffDate)
                    ->limit($batchSize)
                    ->pluck('id');
                
                if ($batch->isEmpty()) {
                    break;
                }
                
                AuditLog::whereIn('id', $batch)->delete();
                $deleted += $batch->count();
                $bar->setProgress($deleted);
                $bar->setMessage("Eliminati {$deleted} log...");
                
                // Piccola pausa per non sovraccaricare il database
                usleep($batchDelay);
                
            } while ($batch->count() === $batchSize);

            $bar->finish();
            $this->newLine(2);
            
            // Statistiche finali
            $remainingLogs = AuditLog::count();
            $this->info("✅ Operazione completata con successo!");
            $this->newLine();
            $this->table(
                ['Metrica', 'Valore'],
                [
                    ['Log eliminati', number_format($deleted)],
                    ['Log rimanenti nel database', number_format($remainingLogs)],
                    ['Spazio liberato', $this->estimateSpaceFreed($deleted)],
                ]
            );

            // Log dell'operazione
            AuditService::log(
                'other',
                "Pulizia automatica log di audit: eliminati {$deleted} log più vecchi di {$days} giorni",
                null,
                [
                    'deleted_count' => $deleted,
                    'retention_days' => $days,
                    'cutoff_date' => $cutoffDate->toDateTimeString(),
                    'archive_file' => $archiveFile ?? null
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error("❌ Errore durante l'operazione: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Archivia i log vecchi in un file CSV.
     */
    protected function archiveLogs(Carbon $cutoffDate): ?string
    {
        try {
            // Usa configurazione per la directory
            $archiveSubDir = config('audit.archive_directory', 'archives/audit-logs');
            $archiveDir = storage_path('app/' . $archiveSubDir);
            
            // Crea directory archivio se non esiste
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
                
                // Crea file README nella directory archivio
                $readmePath = $archiveDir . '/README.txt';
                if (!file_exists($readmePath)) {
                    file_put_contents($readmePath, $this->getArchiveReadme());
                }
            }

            // Nome file con timestamp
            $filename = 'audit_logs_' . $cutoffDate->format('Y-m-d') . '_' . date('Y-m-d_His') . '.csv';
            $filepath = $archiveDir . '/' . $filename;

            // Ottieni i log da archiviare (in batch per memoria)
            $file = fopen($filepath, 'w');
            
            // BOM per Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Intestazioni
            fputcsv($file, [
                'Data/Ora',
                'Utente',
                'Azione',
                'Descrizione',
                'Entità',
                'Nome Entità',
                'Stato',
                'Compagnia'
            ], ';');

            // Scrive i log in batch (usa configurazione)
            $batchSize = config('audit.performance.export_batch_size', 1000);
            
            AuditLog::where('created_at', '<', $cutoffDate)
                ->with(['user', 'compagnia'])
                ->orderBy('created_at')
                ->chunk($batchSize, function ($logs) use ($file) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->created_at->format('d/m/Y H:i:s'),
                            $log->user_name ?? '-',
                            $log->action_label,
                            $log->description,
                            $log->entity_label,
                            $log->entity_name ?? '-',
                            $log->status,
                            $log->compagnia?->nome ?? '-'
                        ], ';');
                    }
                });

            fclose($file);

            return $filename;

        } catch (\Exception $e) {
            $this->error("Errore durante l'archiviazione: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Stima lo spazio liberato (approssimativo).
     */
    protected function estimateSpaceFreed(int $deletedCount): string
    {
        // Stima media: ~500 bytes per log (con JSON, relazioni, etc.)
        $bytes = $deletedCount * 500;
        
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

    /**
     * Contenuto del file README per la directory archivio.
     */
    protected function getArchiveReadme(): string
    {
        return <<<'README'
═══════════════════════════════════════════════════════════════
  ARCHIVIO LOG DI AUDIT - SUGECO
═══════════════════════════════════════════════════════════════

Questa directory contiene i file CSV archiviati dei log di audit
che sono stati rimossi dal database per mantenere le performance.

FORMATO FILE
────────────
Nome file: audit_logs_YYYY-MM-DD_YYYY-MM-DD_HHMMSS.csv

Esempio: audit_logs_2024-01-01_2025-01-27_020000.csv
         └─ Data cutoff ─┘ └─ Data archivio ─┘

CONTENUTO
─────────
Ogni file CSV contiene tutti i log eliminati dal database,
con le seguenti colonne:
- Data/Ora
- Utente
- Azione
- Descrizione
- Entità
- Nome Entità
- Stato
- Compagnia

RETENTION POLICY
────────────────
I log vengono archiviati quando superano il periodo di retention
configurato (default: 365 giorni = 1 anno).

CONFIGURAZIONE
──────────────
Modifica .env per cambiare la retention:
  AUDIT_RETENTION_DAYS=365        (giorni di retention)
  AUDIT_ARCHIVE_BEFORE_DELETE=true (archivia prima di eliminare)

BACKUP
──────
È consigliato eseguire backup periodici di questa directory
su supporto esterno o cloud per conservazione a lungo termine.

MANUTENZIONE
────────────
I file CSV possono essere eliminati manualmente se non più necessari.
Si consiglia di conservarli per almeno 7 anni per compliance.

═══════════════════════════════════════════════════════════════
README;
    }
}
