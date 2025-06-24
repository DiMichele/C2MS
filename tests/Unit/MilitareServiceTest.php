<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MilitareService;
use App\Models\Militare;
use App\Models\Grado;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;
use App\Models\Presenza;
use App\Models\MilitareValutazione;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;

/**
 * Test unitari per il servizio MilitareService
 * 
 * Testa tutte le funzionalità del servizio MilitareService inclusi:
 * - Filtri e ricerche
 * - Gestione valutazioni
 * - Upload e gestione foto
 * - Aggiornamento dati
 * - Cache management
 */
class MilitareServiceTest extends TestCase
{
    protected MilitareService $militareService;

    /**
     * Setup eseguito prima di ogni test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->militareService = new MilitareService();
    }

    /**
     * Test applicazione filtri per grado
     */
    public function test_apply_filters_by_grado(): void
    {
        $grado = Grado::where('nome', 'Capitano')->first();
        
        // Crea militari con gradi diversi
        $this->createTestMilitare(['grado_id' => $grado->id, 'nome' => 'Mario']);
        $this->createTestMilitare(['nome' => 'Luigi']); // Soldato per default
        
        $request = new Request(['grado_id' => $grado->id]);
        $query = Militare::query();
        
        $filteredQuery = $this->militareService->applyFilters($query, $request);
        $results = $filteredQuery->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('Mario', $results->first()->nome);
        $this->assertEquals($grado->id, $results->first()->grado_id);
    }

    /**
     * Test applicazione filtri per plotone
     */
    public function test_apply_filters_by_plotone(): void
    {
        $plotone = Plotone::where('nome', '2° Plotone')->first();
        
        // Crea militari con plotoni diversi
        $this->createTestMilitare(['plotone_id' => $plotone->id, 'nome' => 'Mario']);
        $this->createTestMilitare(['nome' => 'Luigi']); // 1° Plotone per default
        
        $request = new Request(['plotone_id' => $plotone->id]);
        $query = Militare::query();
        
        $filteredQuery = $this->militareService->applyFilters($query, $request);
        $results = $filteredQuery->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('Mario', $results->first()->nome);
        $this->assertEquals($plotone->id, $results->first()->plotone_id);
    }

    /**
     * Test applicazione filtri per presenza
     */
    public function test_apply_filters_by_presenza(): void
    {
        $militare1 = $this->createTestMilitare(['nome' => 'Mario']);
        $militare2 = $this->createTestMilitare(['nome' => 'Luigi']);
        
        // Solo Mario è presente oggi
        Presenza::create([
            'militare_id' => $militare1->id,
            'data' => now()->format('Y-m-d'),
            'stato' => 'Presente'
        ]);
        
        $request = new Request(['presenza' => 'Presente']);
        $query = Militare::query();
        
        $filteredQuery = $this->militareService->applyFilters($query, $request);
        $results = $filteredQuery->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('Mario', $results->first()->nome);
    }

    /**
     * Test ricerca militari per nome
     */
    public function test_search_militari_by_nome(): void
    {
        $this->createTestMilitare(['nome' => 'Mario', 'cognome' => 'Rossi']);
        $this->createTestMilitare(['nome' => 'Luigi', 'cognome' => 'Verdi']);
        $this->createTestMilitare(['nome' => 'Giuseppe', 'cognome' => 'Bianchi']);
        
        $results = $this->militareService->search('Mar');
        
        $this->assertCount(1, $results);
        $this->assertEquals('Mario', $results->first()->nome);
    }

    /**
     * Test ricerca militari per cognome
     */
    public function test_search_militari_by_cognome(): void
    {
        $this->createTestMilitare(['nome' => 'Mario', 'cognome' => 'Rossi']);
        $this->createTestMilitare(['nome' => 'Luigi', 'cognome' => 'Verdi']);
        $this->createTestMilitare(['nome' => 'Giuseppe', 'cognome' => 'Bianchi']);
        
        $results = $this->militareService->search('Ros');
        
        $this->assertCount(1, $results);
        $this->assertEquals('Rossi', $results->first()->cognome);
    }

    /**
     * Test ricerca militari per iniziali
     */
    public function test_search_militari_by_initials(): void
    {
        $this->createTestMilitare(['nome' => 'Mario', 'cognome' => 'Rossi']);
        $this->createTestMilitare(['nome' => 'Luigi', 'cognome' => 'Verdi']);
        
        // Cerca "MR" per Mario Rossi
        $results = $this->militareService->search('MR');
        
        $this->assertCount(1, $results);
        $this->assertEquals('Mario', $results->first()->nome);
        $this->assertEquals('Rossi', $results->first()->cognome);
    }







    /**
     * Test aggiornamento note militare
     */
    public function test_update_notes(): void
    {
        $militare = $this->createTestMilitare();
        $newNotes = 'Nuove note di test';
        
        $result = $this->militareService->updateNotes($militare, $newNotes);
        
        $this->assertTrue($result);
        
        $militare->refresh();
        $this->assertEquals($newNotes, $militare->note);
    }



    /**
     * Test cancellazione foto militare
     */
    public function test_delete_foto(): void
    {
        Storage::fake('public');
        
        $militare = $this->createTestMilitare([
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'foto_path' => 'militari/ROSSI_Mario/foto_profilo.jpg'
        ]);
        
        // Crea il file fittizio
        Storage::disk('public')->put($militare->foto_path, 'fake content');
        
        $result = $this->militareService->deleteFoto($militare);
        
        $this->assertTrue($result);
        
        // Verifica che il file sia stato cancellato
        Storage::disk('public')->assertMissing($militare->foto_path);
        
        // Verifica che il path sia stato rimosso dal database
        $militare->refresh();
        $this->assertNull($militare->foto_path);
    }



    /**
     * Test creazione militare
     */
    public function test_create_militare(): void
    {
        $grado = Grado::where('nome', 'Tenente')->first();
        $plotone = Plotone::where('nome', '3° Plotone')->first();
        
        $data = [
            'nome' => 'Antonio',
            'cognome' => 'Verdi',
            'grado_id' => $grado->id,
            'plotone_id' => $plotone->id,
            'polo_id' => Polo::first()->id,
            'ruolo_id' => Ruolo::first()->id,
            'mansione_id' => Mansione::first()->id
        ];
        
        $militare = $this->militareService->createMilitare($data);
        
        $this->assertInstanceOf(Militare::class, $militare);
        $this->assertEquals('Antonio', $militare->nome);
        $this->assertEquals('Verdi', $militare->cognome);
        $this->assertEquals($grado->id, $militare->grado_id);
        
        $this->assertDatabaseHas('militari', [
            'nome' => 'Antonio',
            'cognome' => 'Verdi'
        ]);
    }



    /**
     * Test validazione regole
     */
    public function test_get_validation_rules(): void
    {
        $rules = $this->militareService->getValidationRules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('nome', $rules);
        $this->assertArrayHasKey('cognome', $rules);
        $this->assertArrayHasKey('grado_id', $rules);
        
        // Test con militare esistente (per update)
        $militare = $this->createTestMilitare();
        $rulesUpdate = $this->militareService->getValidationRules($militare);
        
        $this->assertIsArray($rulesUpdate);
    }

    /**
     * Test ottenimento opzioni per form
     */
    public function test_get_form_options(): void
    {
        $options = $this->militareService->getFormOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('gradi', $options);
        $this->assertArrayHasKey('plotoni', $options);
        $this->assertArrayHasKey('poli', $options);
        $this->assertArrayHasKey('ruoli', $options);
        $this->assertArrayHasKey('mansioni', $options);
        
        $this->assertNotEmpty($options['gradi']);
        $this->assertNotEmpty($options['plotoni']);
    }

    /**
     * Test filtri militari con paginazione
     */
    public function test_get_filtered_militari_with_pagination(): void
    {
        // Crea più militari per testare la paginazione
        for ($i = 1; $i <= 25; $i++) {
            $this->createTestMilitare([
                'nome' => "Militare{$i}",
                'cognome' => "Cognome{$i}"
            ]);
        }
        
        $request = new Request();
        $result = $this->militareService->getFilteredMilitari($request, 10);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('militari', $result);
        $this->assertArrayHasKey('filtri', $result);
        
        // Verifica paginazione
        $militari = $result['militari'];
        $this->assertEquals(10, $militari->count());
        $this->assertTrue($militari->hasPages());
    }

    /**
     * Test ricerca con risultati limitati
     */
    public function test_search_with_limit(): void
    {
        // Crea molti militari con nomi simili
        for ($i = 1; $i <= 15; $i++) {
            $this->createTestMilitare([
                'nome' => "Mario{$i}",
                'cognome' => "Rossi{$i}"
            ]);
        }
        
        $results = $this->militareService->search('Mario', 5);
        
        $this->assertCount(5, $results);
        $this->assertStringContainsString('Mario', $results->first()->nome);
    }
} 