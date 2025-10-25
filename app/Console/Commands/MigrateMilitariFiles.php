<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Militare;
// use App\Models\CertificatiLavoratori; // DEPRECATO - tabelle rimosse
// use App\Models\Idoneita; // DEPRECATO - tabelle rimosse
use Illuminate\Support\Facades\Storage;

class MigrateMilitariFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'militari:migrate-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra i file esistenti (foto e certificati) nelle nuove cartelle personalizzate dei militari';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Migrazione file esistenti nelle cartelle personalizzate...');
        
        // Migra le foto profilo
        $this->migrateFotoProfilo();
        
        // Migra i certificati lavoratori
        $this->migrateCertificatiLavoratori();
        
        // Migra le idoneità
        $this->migrateIdoneita();
        
        $this->info('Migrazione completata!');
        
        return Command::SUCCESS;
    }

    /**
     * Migra le foto profilo esistenti
     */
    private function migrateFotoProfilo()
    {
        $this->info('Migrazione foto profilo...');
        
        $militari = Militare::whereNotNull('foto_path')->get();
        $migrated = 0;
        $errors = 0;

        foreach ($militari as $militare) {
            try {
                $oldPath = $militare->foto_path;
                
                if (Storage::disk('public')->exists($oldPath)) {
                    $newPath = $militare->getFolderPath() . '/foto_profilo.' . pathinfo($oldPath, PATHINFO_EXTENSION);
                    
                    // Copia il file nella nuova posizione
                    Storage::disk('public')->copy($oldPath, $newPath);
                    
                    // Aggiorna il database
                    $militare->update(['foto_path' => $newPath]);
                    
                    // Elimina il file vecchio
                    Storage::disk('public')->delete($oldPath);
                    
                    $migrated++;
                    $this->line("✓ Foto migrata per: {$militare->getNomeCompleto()}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Errore per {$militare->getNomeCompleto()}: " . $e->getMessage());
            }
        }
        
        $this->info("Foto migrate: {$migrated}, Errori: {$errors}");
    }

    /**
     * Migra i certificati lavoratori esistenti
     */
    private function migrateCertificatiLavoratori()
    {
        $this->info('Migrazione certificati lavoratori...');
        
        $certificati = CertificatiLavoratori::whereNotNull('file_path')->with('militare')->get();
        $migrated = 0;
        $errors = 0;

        foreach ($certificati as $certificato) {
            try {
                $oldPath = $certificato->file_path;
                
                if (Storage::disk('public')->exists($oldPath) && $certificato->militare) {
                    $fileName = basename($oldPath);
                    $newPath = $certificato->militare->getCertificatiLavoratoriPath() . '/' . $fileName;
                    
                    // Copia il file nella nuova posizione
                    Storage::disk('public')->copy($oldPath, $newPath);
                    
                    // Aggiorna il database
                    $certificato->update(['file_path' => $newPath]);
                    
                    // Elimina il file vecchio
                    Storage::disk('public')->delete($oldPath);
                    
                    $migrated++;
                    $this->line("✓ Certificato migrato: {$certificato->tipo} per {$certificato->militare->getNomeCompleto()}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Errore certificato ID {$certificato->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Certificati lavoratori migrati: {$migrated}, Errori: {$errors}");
    }

    /**
     * Migra le idoneità esistenti
     */
    private function migrateIdoneita()
    {
        $this->info('Migrazione idoneità...');
        
        $idoneita = Idoneita::whereNotNull('file_path')->with('militare')->get();
        $migrated = 0;
        $errors = 0;

        foreach ($idoneita as $idoneitaItem) {
            try {
                $oldPath = $idoneitaItem->file_path;
                
                if (Storage::disk('public')->exists($oldPath) && $idoneitaItem->militare) {
                    $fileName = basename($oldPath);
                    $newPath = $idoneitaItem->militare->getIdoneitaPath() . '/' . $fileName;
                    
                    // Copia il file nella nuova posizione
                    Storage::disk('public')->copy($oldPath, $newPath);
                    
                    // Aggiorna il database
                    $idoneitaItem->update(['file_path' => $newPath]);
                    
                    // Elimina il file vecchio
                    Storage::disk('public')->delete($oldPath);
                    
                    $migrated++;
                    $this->line("✓ Idoneità migrata: {$idoneitaItem->tipo} per {$idoneitaItem->militare->getNomeCompleto()}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Errore idoneità ID {$idoneitaItem->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Idoneità migrate: {$migrated}, Errori: {$errors}");
    }
} 