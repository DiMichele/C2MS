<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mansione;
use App\Models\Polo;
use App\Models\Compagnia;
use Illuminate\Support\Facades\DB;

class UpdateMansioniPoliSeeder extends Seeder
{
    /**
     * Aggiorna mansioni e poli con i nuovi valori richiesti
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // ==========================================
            // AGGIORNA MANSIONI (INCARICHI)
            // ==========================================
            
            $this->command->info('🔄 Aggiornamento mansioni...');
            
            // Prima rimuovi i riferimenti dai militari
            DB::table('militari')->update(['mansione_id' => null]);
            
            // Poi cancella tutte le mansioni esistenti
            Mansione::query()->delete();
            
            $mansioni = [
                'Comandante di Compagnia',
                'Vice Comandante di Compagnia',
                'Comandante di plotone',
                'Operatore per le Telecomunicazioni',
                'Operatore Informatico',
                'Radiofonista',
                'Pontista',
                'Gruppista'
            ];
            
            foreach ($mansioni as $nome) {
                Mansione::create([
                    'nome' => $nome,
                    'descrizione' => "Incarico: $nome"
                ]);
            }
            
            $this->command->info("✅ Create " . count($mansioni) . " mansioni.");
            
            // ==========================================
            // AGGIORNA POLI (UFFICI)
            // ==========================================
            
            $this->command->info('🔄 Aggiornamento poli...');
            
            // Prima rimuovi i riferimenti dai militari
            DB::table('militari')->update(['polo_id' => null]);
            
            // Poi cancella tutti i poli esistenti
            Polo::query()->delete();
            
            $uffici = [
                'Ufficio Comando',
                'Ufficio di Compagnia',
                'Ufficio Auto',
                'Magazzino Gruppi Elettrogeni',
                'Magazzino Radio',
                'Magazzino Informatico',
                'Magazzino Satellitare',
                'N.C.T.',
                'N.G.S.I.'
            ];
            
            // Ottieni tutte le compagnie
            $compagnie = Compagnia::all();
            
            if ($compagnie->isEmpty()) {
                $this->command->warn('⚠️  Nessuna compagnia trovata. Creo una compagnia di default.');
                
                $compagnia = Compagnia::create([
                    'nome' => '124^ Compagnia',
                    'descrizione' => 'Compagnia di default'
                ]);
                
                $compagnie = collect([$compagnia]);
            }
            
            // Crea i poli per ogni compagnia
            $totalePoli = 0;
            foreach ($compagnie as $compagnia) {
                foreach ($uffici as $ufficio) {
                    Polo::create([
                        'nome' => $ufficio,
                        'compagnia_id' => $compagnia->id,
                        'descrizione' => "Ufficio: $ufficio"
                    ]);
                    $totalePoli++;
                }
            }
            
            $this->command->info("✅ Creati $totalePoli poli per " . $compagnie->count() . " compagnia/e.");
            
            DB::commit();
            
            $this->command->info('');
            $this->command->info('====================================');
            $this->command->info('✅ AGGIORNAMENTO COMPLETATO');
            $this->command->info('====================================');
            $this->command->info('📋 Mansioni: ' . count($mansioni));
            $this->command->info('🏢 Uffici: ' . count($uffici));
            $this->command->info('====================================');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->command->error('❌ Errore durante l\'aggiornamento:');
            $this->command->error($e->getMessage());
            
            throw $e;
        }
    }
}

