<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--restore : Ripristina il backup precedente}';
    protected $description = 'Crea un backup del database o ripristina il backup precedente';

    public function handle()
    {
        if ($this->option('restore')) {
            return $this->restoreBackup();
        }

        return $this->createBackup();
    }

    private function createBackup()
    {
        $this->info('🔄 Creazione backup del database...');
        
        try {
            // Nome del file di backup con timestamp
            $backupFile = storage_path('app/backup_' . date('Y-m-d_H-i-s') . '.sql');
            
            // Comando mysqldump per creare il backup
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s --password=%s %s > %s',
                $dbHost,
                $dbPort,
                $dbUser,
                $dbPass,
                $dbName,
                $backupFile
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                // Salva anche il percorso dell'ultimo backup
                file_put_contents(storage_path('app/last_backup.txt'), $backupFile);
                
                $this->info('✅ Backup creato con successo: ' . basename($backupFile));
                $this->line('📁 Percorso: ' . $backupFile);
            } else {
                $this->error('❌ Errore nella creazione del backup');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Errore: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    private function restoreBackup()
    {
        $this->info('🔄 Ripristino del backup...');
        
        try {
            // Leggi l'ultimo backup
            $lastBackupFile = storage_path('app/last_backup.txt');
            
            if (!File::exists($lastBackupFile)) {
                $this->error('❌ Nessun backup trovato');
                return 1;
            }
            
            $backupFile = file_get_contents($lastBackupFile);
            
            if (!File::exists($backupFile)) {
                $this->error('❌ File di backup non trovato: ' . basename($backupFile));
                return 1;
            }
            
            // Comando mysql per ripristinare il backup
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            
            $command = sprintf(
                'mysql -h %s -P %s -u %s --password=%s %s < %s',
                $dbHost,
                $dbPort,
                $dbUser,
                $dbPass,
                $dbName,
                $backupFile
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->info('✅ Database ripristinato con successo');
                $this->line('📁 Dal backup: ' . basename($backupFile));
            } else {
                $this->error('❌ Errore nel ripristino del backup');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Errore: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
} 