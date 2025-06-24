<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Evento;
use App\Models\Militare;
use Carbon\Carbon;

/**
 * Test per il modello Evento
 */
class EventoModelTest extends TestCase
{
    /**
     * Test creazione evento con dati validi
     */
    public function test_evento_creation_with_valid_data(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $evento = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Esercitazione Tattica',
            'data_inizio' => '2025-01-15',
            'data_fine' => '2025-01-17',
            'localita' => 'Campo di Addestramento'
        ]);

        $this->assertDatabaseHas('eventi', [
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Esercitazione Tattica'
        ]);

        $this->assertEquals('Addestramento', $evento->tipologia);
        $this->assertEquals('Esercitazione Tattica', $evento->nome);
    }

    /**
     * Test relazione con militare
     */
    public function test_evento_belongs_to_militare(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare(['nome' => 'Test Evento']);
        
        $evento = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Missione',
            'nome' => 'Operazione Alpha',
            'data_inizio' => now()->format('Y-m-d'),
            'data_fine' => now()->addDays(3)->format('Y-m-d'),
            'localita' => 'Teatro Operativo'
        ]);

        $this->assertInstanceOf(Militare::class, $evento->militare);
        $this->assertEquals($militare->id, $evento->militare->id);
        $this->assertEquals('Test Evento', $evento->militare->nome);
    }

    /**
     * Test scope eventi attivi oggi
     */
    public function test_scope_attivi_oggi(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $oggi = Carbon::today();
        
        // Evento attivo oggi
        $eventoAttivo = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Servizio',
            'nome' => 'Evento Attivo',
            'data_inizio' => $oggi->subDay()->format('Y-m-d'),
            'data_fine' => $oggi->addDays(2)->format('Y-m-d'),
            'localita' => 'Base'
        ]);
        
        // Evento passato
        Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Servizio',
            'nome' => 'Evento Passato',
            'data_inizio' => $oggi->subDays(5)->format('Y-m-d'),
            'data_fine' => $oggi->subDays(3)->format('Y-m-d'),
            'localita' => 'Base'
        ]);

        $eventiOggi = Evento::attiviOggi()->get();
        
        $this->assertCount(1, $eventiOggi);
        $this->assertEquals('Evento Attivo', $eventiOggi->first()->nome);
    }

    /**
     * Test scope eventi futuri
     */
    public function test_scope_futuri(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $domani = Carbon::tomorrow();
        
        // Evento futuro
        $eventoFuturo = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Evento Futuro',
            'data_inizio' => $domani->format('Y-m-d'),
            'data_fine' => $domani->addDays(2)->format('Y-m-d'),
            'localita' => 'Campo Addestramento'
        ]);
        
        // Evento passato
        Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Servizio',
            'nome' => 'Evento Passato',
            'data_inizio' => Carbon::yesterday()->subDays(2)->format('Y-m-d'),
            'data_fine' => Carbon::yesterday()->format('Y-m-d'),
            'localita' => 'Base'
        ]);

        $eventiFuturi = Evento::futuri()->get();
        
        $this->assertCount(1, $eventiFuturi);
        $this->assertEquals('Evento Futuro', $eventiFuturi->first()->nome);
    }

    /**
     * Test scope eventi passati
     */
    public function test_scope_passati(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $ieri = Carbon::yesterday();
        
        // Evento passato
        $eventoPassato = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Missione',
            'nome' => 'Evento Passato',
            'data_inizio' => $ieri->subDays(3)->format('Y-m-d'),
            'data_fine' => $ieri->format('Y-m-d'),
            'localita' => 'Teatro Operativo'
        ]);
        
        // Evento futuro
        Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Evento Futuro',
            'data_inizio' => Carbon::tomorrow()->format('Y-m-d'),
            'data_fine' => Carbon::tomorrow()->addDays(2)->format('Y-m-d'),
            'localita' => 'Campo'
        ]);

        $eventiPassati = Evento::passati()->get();
        
        $this->assertCount(1, $eventiPassati);
        $this->assertEquals('Evento Passato', $eventiPassati->first()->nome);
    }

    /**
     * Test scope per tipologia
     */
    public function test_scope_per_tipologia(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        // Eventi di addestramento
        Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Corso Alpha',
            'data_inizio' => '2025-02-01',
            'data_fine' => '2025-02-03',
            'localita' => 'Centro Formazione'
        ]);
        
        Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Corso Beta',
            'data_inizio' => '2025-02-05',
            'data_fine' => '2025-02-07',
            'localita' => 'Centro Formazione'
        ]);
        
        // Evento di missione
        Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Missione',
            'nome' => 'Operazione Gamma',
            'data_inizio' => '2025-02-10',
            'data_fine' => '2025-02-15',
            'localita' => 'Zone Operative'
        ]);

        $eventiAddestramento = Evento::perTipologia('Addestramento')->get();
        
        $this->assertCount(2, $eventiAddestramento);
        $this->assertTrue($eventiAddestramento->pluck('nome')->contains('Corso Alpha'));
        $this->assertTrue($eventiAddestramento->pluck('nome')->contains('Corso Beta'));
    }

    /**
     * Test durata evento
     */
    public function test_durata_evento(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $evento = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Corso Lungo',
            'data_inizio' => '2025-03-01',
            'data_fine' => '2025-03-07', // 7 giorni
            'localita' => 'Centro Specialistico'
        ]);

        // Calcoliamo manualmente la durata come test
        $dataInizio = \Carbon\Carbon::parse($evento->data_inizio);
        $dataFine = \Carbon\Carbon::parse($evento->data_fine);
        $durata = $dataInizio->diffInDays($dataFine) + 1;
        $this->assertEquals(7, $durata);
    }

    /**
     * Test verifica sovrapposizione eventi (simulazione logica)
     */
    public function test_verifica_sovrapposizione(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        // Evento esistente
        $evento1 = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Evento 1',
            'data_inizio' => '2025-04-01',
            'data_fine' => '2025-04-05',
            'localita' => 'Centro A'
        ]);

        // Simuliamo la logica di sovrapposizione
        $testDataInizio = '2025-04-03';
        $testDataFine = '2025-04-07';
        
        $hasConflict = ($testDataInizio <= $evento1->data_fine && $testDataFine >= $evento1->data_inizio);
        $this->assertTrue($hasConflict);
        
        // Test nessuna sovrapposizione
        $testDataInizio2 = '2025-04-10';
        $testDataFine2 = '2025-04-15';
        $noConflict = ($testDataInizio2 <= $evento1->data_fine && $testDataFine2 >= $evento1->data_inizio);
        $this->assertFalse($noConflict);
    }

    /**
     * Test casting date Carbon
     */
    public function test_date_casting(): void
    {
        $this->seedBasicData();
        $militare = $this->createTestMilitare();
        
        $evento = Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Test',
            'nome' => 'Test Date',
            'data_inizio' => '2025-05-01',
            'data_fine' => '2025-05-03',
            'localita' => 'Test Location'
        ]);

        $this->assertInstanceOf(Carbon::class, $evento->data_inizio);
        $this->assertInstanceOf(Carbon::class, $evento->data_fine);
    }

    /**
     * Test fillable attributes
     */
    public function test_fillable_attributes(): void
    {
        $evento = new Evento();
        
        $expected = [
            'militare_id', 'tipologia', 'nome', 
            'data_inizio', 'data_fine', 'localita'
        ];
        
        $this->assertEquals($expected, $evento->getFillable());
    }

    /**
     * Test nome tabella
     */
    public function test_table_name(): void
    {
        $evento = new Evento();
        $this->assertEquals('eventi', $evento->getTable());
    }
} 