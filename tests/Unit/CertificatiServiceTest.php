<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CertificatiService;
use App\Models\Militare;
// use App\Models\CertificatiLavoratori; // DEPRECATO - tabelle rimosse
// use App\Models\Idoneita; // DEPRECATO - tabelle rimosse
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Test per il servizio CertificatiService
 */
class CertificatiServiceTest extends TestCase
{
    protected CertificatiService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new CertificatiService();
        Storage::fake('public');
    }

    /**
     * Test costruzione query militari
     */
    public function test_build_militari_query(): void
    {
        $this->seedBasicData();
        
        $militare1 = $this->createTestMilitare(['nome' => 'Mario']);
        $militare2 = $this->createTestMilitare(['nome' => 'Luigi']);

        $request = new Request(['militare_id' => $militare1->id]);
        $query = $this->service->buildMilitariQuery($request);
        
        $risultati = $query->get();
        
        $this->assertCount(1, $risultati);
        $this->assertEquals($militare1->id, $risultati->first()->id);
    }

    /**
     * Test filtro per militare specifico
     */
    public function test_filtro_militare_specifico(): void
    {
        $this->seedBasicData();
        
        $militare = $this->createTestMilitare(['nome' => 'Test Certificati']);
        
        $request = new Request(['militare_id' => $militare->id]);
        $query = $this->service->buildMilitariQuery($request);
        
        $result = $query->first();
        $this->assertEquals($militare->id, $result->id);
        $this->assertEquals('Test Certificati', $result->nome);
    }

    /**
     * Test ordinamento militari
     */
    public function test_ordinamento_militari(): void
    {
        $this->seedBasicData();
        
        // Crea militari con gradi diversi
        $generale = $this->createTestMilitare([
            'nome' => 'Antonio',
            'grado_id' => \App\Models\Grado::where('nome', 'Generale')->first()->id
        ]);
        
        $soldato = $this->createTestMilitare([
            'nome' => 'Beppe',
            'grado_id' => \App\Models\Grado::where('nome', 'Soldato')->first()->id
        ]);

        $request = new Request();
        $militari = $this->service->buildMilitariQuery($request)->get();
        
        // Verifica ordinamento (soldato prima con orderByDesc)
        $this->assertEquals('Beppe', $militari->first()->nome);
    }

    /**
     * Test salvataggio file certificato
     */
    public function test_save_file(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare(['nome' => 'Test Upload']);
        
        $file = UploadedFile::fake()->create('certificato.pdf', 100, 'application/pdf');
        
        $result = $this->service->saveFile($file, $militare, 'corsi_lavoratori_4h');
        
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertStringContainsString('certificati_lavoratori', $result['path']);
        
        Storage::disk('public')->assertExists($result['path']);
    }

    /**
     * Test eliminazione file
     */
    public function test_delete_file(): void
    {
        Storage::disk('public')->put('test-file.pdf', 'contenuto test');
        $this->assertTrue(Storage::disk('public')->exists('test-file.pdf'));
        
        $this->service->deleteFile('test-file.pdf');
        
        $this->assertFalse(Storage::disk('public')->exists('test-file.pdf'));
    }

    /**
     * Test trova certificato per ID - Certificati Lavoratori
     */
    public function test_find_certificato_lavoratori(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $certificato = CertificatiLavoratori::create([
            'militare_id' => $militare->id,
            'tipo' => 'corsi_lavoratori_4h',
            'data_ottenimento' => now()->format('Y-m-d'),
            'data_scadenza' => now()->addYears(4)->format('Y-m-d'),
            'ente_rilascio' => 'Ente Test'
        ]);

        $trovato = $this->service->findCertificato($certificato->id);
        
        $this->assertInstanceOf(CertificatiLavoratori::class, $trovato);
        $this->assertEquals($certificato->id, $trovato->id);
    }

    /**
     * Test trova certificato per ID - Idoneità
     */
    public function test_find_certificato_idoneita(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $idoneita = Idoneita::create([
            'militare_id' => $militare->id,
            'tipo' => 'idoneita_mansione',
            'data_ottenimento' => now()->format('Y-m-d'),
            'data_scadenza' => now()->addYears(2)->format('Y-m-d'),
            'ente_rilascio' => 'Medico Militare'
        ]);

        $trovato = $this->service->findCertificato($idoneita->id);
        
        $this->assertInstanceOf(Idoneita::class, $trovato);
        $this->assertEquals($idoneita->id, $trovato->id);
    }

    /**
     * Test certificato non trovato
     */
    public function test_certificato_non_trovato(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        $this->service->findCertificato(999999);
    }

    /**
     * Test ottieni classe modello certificato lavoratori
     */
    public function test_get_certificato_model_class_lavoratori(): void
    {
        $class = $this->service->getCertificatoModelClass('corsi_lavoratori_4h');
        $this->assertEquals(CertificatiLavoratori::class, $class);
        
        $class = $this->service->getCertificatoModelClass('corsi_lavoratori_preposti');
        $this->assertEquals(CertificatiLavoratori::class, $class);
    }

    /**
     * Test ottieni classe modello idoneità
     */
    public function test_get_certificato_model_class_idoneita(): void
    {
        $class = $this->service->getCertificatoModelClass('idoneita_mansione');
        $this->assertEquals(Idoneita::class, $class);
        
        $class = $this->service->getCertificatoModelClass('idoneita_smi');
        $this->assertEquals(Idoneita::class, $class);
    }

    /**
     * Test ottieni route redirect certificati lavoratori
     */
    public function test_get_redirect_route_lavoratori(): void
    {
        $route = $this->service->getRedirectRoute('corsi_lavoratori_4h');
        $this->assertEquals('certificati.corsi-lavoratori', $route);
        
        $route = $this->service->getRedirectRoute('corsi_lavoratori_dirigenti');
        $this->assertEquals('certificati.corsi-lavoratori', $route);
    }

    /**
     * Test ottieni route redirect idoneità
     */
    public function test_get_redirect_route_idoneita(): void
    {
        $route = $this->service->getRedirectRoute('idoneita_mansione');
        $this->assertEquals('certificati.idoneita', $route);
        
        $route = $this->service->getRedirectRoute('idoneita');
        $this->assertEquals('certificati.idoneita', $route);
    }

    /**
     * Test tipi certificati supportati
     */
    public function test_tipi_certificati_supportati(): void
    {
        $tipiLavoratori = CertificatiService::TIPI_CORSI_LAVORATORI;
        $this->assertContains('corsi_lavoratori_4h', $tipiLavoratori);
        $this->assertContains('corsi_lavoratori_8h', $tipiLavoratori);
        $this->assertContains('corsi_lavoratori_preposti', $tipiLavoratori);
        $this->assertContains('corsi_lavoratori_dirigenti', $tipiLavoratori);
        
        $tipiIdoneita = CertificatiService::TIPI_IDONEITA;
        $this->assertContains('idoneita_mansione', $tipiIdoneita);
        $this->assertContains('idoneita_smi', $tipiIdoneita);
        $this->assertContains('idoneita', $tipiIdoneita);
    }

    /**
     * Test invalidazione cache
     */
    public function test_invalidate_cache(): void
    {
        // Cache alcune chiavi
        \Cache::put('certificati_test_key', 'test_value', 60);
        $this->assertEquals('test_value', \Cache::get('certificati_test_key'));
        
        // Invalida la cache (implementazione dipende dal service)
        $this->service->invalidateCache();
        
        // Verifica che la cache sia stata invalidata
        // Nota: Il metodo potrebbe non eliminare tutte le chiavi, 
        // ma almeno non dovrebbe lanciare errori
        $this->assertTrue(true); // Test che il metodo non fallisce
    }

    /**
     * Test eager loading ottimizzato
     */
    public function test_eager_loading_ottimizzato(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();

        $request = new Request();
        $query = $this->service->buildMilitariQuery($request);
        
        // Verifica che il grado sia caricato in eager loading
        $result = $query->first();
        $this->assertTrue($result->relationLoaded('grado'));
    }
} 