<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Militare;
use App\Models\Grado;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;
use App\Models\Presenza;
use App\Models\Assenza;
use App\Models\Evento;
// use App\Models\CertificatiLavoratori; // DEPRECATO - tabelle rimosse
use App\Models\MilitareValutazione;
use Carbon\Carbon;

/**
 * Test unitari per il modello Militare
 * 
 * Testa tutte le funzionalità del modello Militare inclusi:
 * - Relazioni Eloquent
 * - Query Scopes
 * - Metodi di business logic
 * - Attributi calcolati
 * - Gestione delle directory
 */
class MilitareModelTest extends TestCase
{
    /**
     * Test creazione di un militare con dati validi
     */
    public function test_militare_creation_with_valid_data(): void
    {
        $militare = $this->createTestMilitare([
            'nome' => 'Giuseppe',
            'cognome' => 'Verdi',
            'certificati_note' => 'Note sui certificati',
            'idoneita_note' => 'Note sull\'idoneità'
        ]);

        $this->assertDatabaseHas('militari', [
            'nome' => 'Giuseppe',
            'cognome' => 'Verdi',
            'certificati_note' => 'Note sui certificati',
            'idoneita_note' => 'Note sull\'idoneità'
        ]);

        $this->assertInstanceOf(Militare::class, $militare);
        $this->assertEquals('Giuseppe', $militare->nome);
        $this->assertEquals('Verdi', $militare->cognome);
    }

    /**
     * Test relazione con il grado
     */
    public function test_militare_belongs_to_grado(): void
    {
        $grado = Grado::where('nome', 'Capitano')->first();
        $militare = $this->createTestMilitare(['grado_id' => $grado->id]);

        $this->assertInstanceOf(Grado::class, $militare->grado);
        $this->assertEquals('Capitano', $militare->grado->nome);
    }

    /**
     * Test relazione con plotone
     */
    public function test_militare_belongs_to_plotone(): void
    {
        $plotone = Plotone::where('nome', '2° Plotone')->first();
        $militare = $this->createTestMilitare(['plotone_id' => $plotone->id]);

        $this->assertInstanceOf(Plotone::class, $militare->plotone);
        $this->assertEquals('2° Plotone', $militare->plotone->nome);
    }

    /**
     * Test relazione con polo
     */
    public function test_militare_belongs_to_polo(): void
    {
        $polo = Polo::where('nome', 'Polo Centro')->first();
        $militare = $this->createTestMilitare(['polo_id' => $polo->id]);

        $this->assertInstanceOf(Polo::class, $militare->polo);
        $this->assertEquals('Polo Centro', $militare->polo->nome);
    }

    /**
     * Test relazione con ruolo
     */
    public function test_militare_belongs_to_ruolo(): void
    {
        $ruolo = Ruolo::where('nome', 'Supporto')->first();
        $militare = $this->createTestMilitare(['ruolo_id' => $ruolo->id]);

        $this->assertInstanceOf(Ruolo::class, $militare->ruolo);
        $this->assertEquals('Supporto', $militare->ruolo->nome);
    }

    /**
     * Test relazione con mansione
     */
    public function test_militare_belongs_to_mansione(): void
    {
        $mansione = Mansione::where('nome', 'Medico')->first();
        $militare = $this->createTestMilitare(['mansione_id' => $mansione->id]);

        $this->assertInstanceOf(Mansione::class, $militare->mansione);
        $this->assertEquals('Medico', $militare->mansione->nome);
    }

    /**
     * Test metodo getNomeCompleto senza grado
     */
    public function test_get_nome_completo_without_grado(): void
    {
        $militare = $this->createTestMilitare([
            'nome' => 'Luigi',
            'cognome' => 'Bianchi'
        ]);

        $nomeCompleto = $militare->getNomeCompleto(false);
        $this->assertEquals('Bianchi Luigi', $nomeCompleto);
    }

    /**
     * Test metodo getNomeCompleto con grado
     */
    public function test_get_nome_completo_with_grado(): void
    {
        $grado = Grado::where('nome', 'Sergente')->first();
        $militare = $this->createTestMilitare([
            'nome' => 'Luigi',
            'cognome' => 'Bianchi',
            'grado_id' => $grado->id
        ]);

        $nomeCompleto = $militare->getNomeCompleto(true);
        $this->assertEquals('Sergente Bianchi Luigi', $nomeCompleto);
    }

    /**
     * Test scope orderByGradoENome
     */
    public function test_scope_order_by_grado_e_nome(): void
    {
        // Crea militari con gradi diversi
        $colonnello = Grado::where('nome', 'Colonnello')->first();
        $soldato = Grado::where('nome', 'Soldato')->first();
        $capitano = Grado::where('nome', 'Capitano')->first();

        $militare1 = $this->createTestMilitare([
            'nome' => 'Carlo', 'cognome' => 'Rossi', 'grado_id' => $soldato->id
        ]);
        $militare2 = $this->createTestMilitare([
            'nome' => 'Antonio', 'cognome' => 'Verdi', 'grado_id' => $colonnello->id
        ]);
        $militare3 = $this->createTestMilitare([
            'nome' => 'Bruno', 'cognome' => 'Bianchi', 'grado_id' => $capitano->id
        ]);

        $militari = Militare::orderByGradoENome()->get();

        // Con orderByDesc('gradi.ordine'), il soldato (ordine 10) viene primo
        $this->assertEquals('Carlo', $militari->first()->nome);
        // Il colonnello (ordine 2) viene ultimo
        $this->assertEquals('Antonio', $militari->last()->nome);
    }

    /**
     * Test metodo isPresente con presenza odierna
     */
    public function test_is_presente_with_today_presence(): void
    {
        $militare = $this->createTestMilitare();
        
        // Crea una presenza per oggi
        Presenza::create([
            'militare_id' => $militare->id,
            'data' => now()->format('Y-m-d'),
            'stato' => 'Presente'
        ]);

        $this->assertTrue($militare->isPresente());
    }

    /**
     * Test metodo isPresente senza presenza odierna
     */
    public function test_is_presente_without_today_presence(): void
    {
        $militare = $this->createTestMilitare();
        
        // Non creare nessuna presenza per oggi
        $this->assertFalse($militare->isPresente());
    }



    /**
     * Test metodo hasEventoInDate con evento presente
     */
    public function test_has_evento_in_date_with_existing_evento(): void
    {
        $militare = $this->createTestMilitare();
        $dates = $this->createTestDates();
        
        // Crea un evento che include la data odierna
        Evento::create([
            'militare_id' => $militare->id,
            'tipologia' => 'Addestramento',
            'nome' => 'Esercitazione',
            'data_inizio' => $dates['ieri'],
            'data_fine' => $dates['domani'],
            'localita' => 'Base Militare'
        ]);

        $hasEvento = $militare->hasEventoInDate($dates['oggi'], $dates['oggi']);
        $this->assertTrue($hasEvento);
    }

    /**
     * Test metodo hasEventoInDate senza eventi
     */
    public function test_has_evento_in_date_without_evento(): void
    {
        $militare = $this->createTestMilitare();
        $dates = $this->createTestDates();

        $hasEvento = $militare->hasEventoInDate($dates['oggi'], $dates['oggi']);
        $this->assertFalse($hasEvento);
    }

    /**
     * Test metodo getFolderName
     */
    public function test_get_folder_name(): void
    {
        $militare = $this->createTestMilitare([
            'nome' => 'Giuseppe',
            'cognome' => 'Verdi'
        ]);

        $folderName = $militare->getFolderName();
        $expectedName = 'Verdi_Giuseppe_' . $militare->id;
        $this->assertEquals($expectedName, $folderName);
    }

    /**
     * Test metodo getFolderPath
     */
    public function test_get_folder_path(): void
    {
        $militare = $this->createTestMilitare([
            'nome' => 'Giuseppe',
            'cognome' => 'Verdi'
        ]);

        $folderPath = $militare->getFolderPath();
        $expectedPath = 'militari/Verdi_Giuseppe_' . $militare->id;
        $this->assertEquals($expectedPath, $folderPath);
    }

    /**
     * Test metodo hasCertificatiValidi con certificati validi
     */
    public function test_has_certificati_validi_with_valid_certificates(): void
    {
        $militare = $this->createTestMilitare();
        
        // Crea TUTTI i certificati richiesti dal metodo
        $tipiRichiesti = ['corsi_lavoratori_4h', 'corsi_lavoratori_8h', 'corsi_lavoratori_preposti', 'corsi_lavoratori_dirigenti'];
        
        foreach ($tipiRichiesti as $tipo) {
            CertificatiLavoratori::create([
                'militare_id' => $militare->id,
                'tipo' => $tipo,
                'data_ottenimento' => now()->subMonth()->format('Y-m-d'),
                'data_scadenza' => now()->addYears(4)->format('Y-m-d')
            ]);
        }

        $this->assertTrue($militare->hasCertificatiValidi());
    }

    /**
     * Test metodo hasCertificatiValidi senza certificati
     */
    public function test_has_certificati_validi_without_certificates(): void
    {
        $militare = $this->createTestMilitare();
        
        $this->assertFalse($militare->hasCertificatiValidi());
    }

    /**
     * Test attributo calcolato media_valutazioni
     */
    public function test_media_valutazioni_attribute(): void
    {
        $militare = $this->createTestMilitare();
        
        // Crea una valutazione
        $valutatore = User::create([
            'name' => 'Valutatore Test',
            'email' => 'valutatore@test.com',
            'password' => bcrypt('password')
        ]);
        MilitareValutazione::create([
            'militare_id' => $militare->id,
            'valutatore_id' => $valutatore->id,
            'precisione_lavoro' => 8,
            'affidabilita' => 9,
            'capacita_tecnica' => 7,
            'collaborazione' => 6,
            'iniziativa' => 8
        ]);

        // Ricarica il militare per ottenere la valutazione
        $militare->refresh();
        
        // La media dovrebbe essere (8+9+7+6+8)/5 = 7.6
        $this->assertEquals(7.6, $militare->media_valutazioni);
    }

    /**
     * Test attributo media_valutazioni senza valutazioni
     */
    public function test_media_valutazioni_attribute_without_evaluations(): void
    {
        $militare = $this->createTestMilitare();
        
        $this->assertEquals(0, $militare->media_valutazioni);
    }

    /**
     * Test scope presenti
     */
    public function test_scope_presenti(): void
    {
        $militare1 = $this->createTestMilitare(['nome' => 'Mario']);
        $militare2 = $this->createTestMilitare(['nome' => 'Luigi']);
        
        // Solo militare1 è presente oggi
        Presenza::create([
            'militare_id' => $militare1->id,
            'data' => now()->format('Y-m-d'),
            'stato' => 'Presente'
        ]);

        $presenti = Militare::presenti()->get();
        
        $this->assertCount(1, $presenti);
        $this->assertEquals('Mario', $presenti->first()->nome);
    }

    /**
     * Test scope assenti
     */
    public function test_scope_assenti(): void
    {
        $militare1 = $this->createTestMilitare(['nome' => 'Mario']);
        $militare2 = $this->createTestMilitare(['nome' => 'Luigi']);
        
        // Solo militare1 è presente oggi
        Presenza::create([
            'militare_id' => $militare1->id,
            'data' => now()->format('Y-m-d'),
            'stato' => 'Presente'
        ]);

        $assenti = Militare::assenti()->get();
        
        $this->assertCount(1, $assenti);
        $this->assertEquals('Luigi', $assenti->first()->nome);
    }

    /**
     * Test scope inEvento
     */
    public function test_scope_in_evento(): void
    {
        $militare1 = $this->createTestMilitare(['nome' => 'Mario']);
        $militare2 = $this->createTestMilitare(['nome' => 'Luigi']);
        $dates = $this->createTestDates();
        
        // Solo militare1 ha un evento oggi
        Evento::create([
            'militare_id' => $militare1->id,
            'tipologia' => 'Missione',
            'nome' => 'Operazione Alpha',
            'data_inizio' => $dates['oggi'],
            'data_fine' => $dates['domani'],
            'localita' => 'Teatro Operativo'
        ]);

        $inEvento = Militare::inEvento($dates['oggi'])->get();
        
        $this->assertCount(1, $inEvento);
        $this->assertEquals('Mario', $inEvento->first()->nome);
    }
} 