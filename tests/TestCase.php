<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Militare;
use App\Models\Grado;
use App\Models\Plotone;
use App\Models\Compagnia;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, WithFaker;

    /**
     * Setup eseguito prima di ogni test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed dei dati di base necessari per i test
        $this->seedBasicData();
    }

    /**
     * Crea dati di base necessari per i test
     */
    protected function seedBasicData(): void
    {
        // Crea gradi di base
        Grado::create(['nome' => 'Generale', 'ordine' => 1, 'ordine_precedenza' => 1]);
        Grado::create(['nome' => 'Colonnello', 'ordine' => 2, 'ordine_precedenza' => 2]); 
        Grado::create(['nome' => 'Maggiore', 'ordine' => 3, 'ordine_precedenza' => 3]);
        Grado::create(['nome' => 'Capitano', 'ordine' => 4, 'ordine_precedenza' => 4]);
        Grado::create(['nome' => 'Tenente', 'ordine' => 5, 'ordine_precedenza' => 5]);
        Grado::create(['nome' => 'Sottotenente', 'ordine' => 6, 'ordine_precedenza' => 6]);
        Grado::create(['nome' => 'Maresciallo', 'ordine' => 7, 'ordine_precedenza' => 7]);
        Grado::create(['nome' => 'Sergente', 'ordine' => 8, 'ordine_precedenza' => 8]);
        Grado::create(['nome' => 'Caporale', 'ordine' => 9, 'ordine_precedenza' => 9]);
        Grado::create(['nome' => 'Soldato', 'ordine' => 10, 'ordine_precedenza' => 10]);

        // Crea compagnia di base
        $compagnia = Compagnia::create(['nome' => 'Compagnia Test']);

        // Crea plotoni di base
        Plotone::create(['nome' => '1° Plotone', 'compagnia_id' => $compagnia->id]);
        Plotone::create(['nome' => '2° Plotone', 'compagnia_id' => $compagnia->id]);
        Plotone::create(['nome' => '3° Plotone', 'compagnia_id' => $compagnia->id]);

        // Crea poli di base
        Polo::create(['nome' => 'Polo Nord', 'compagnia_id' => $compagnia->id]);
        Polo::create(['nome' => 'Polo Sud', 'compagnia_id' => $compagnia->id]);
        Polo::create(['nome' => 'Polo Centro', 'compagnia_id' => $compagnia->id]);

        // Crea ruoli di base
        Ruolo::create(['nome' => 'Combattente']);
        Ruolo::create(['nome' => 'Supporto']);
        Ruolo::create(['nome' => 'Logistica']);

        // Crea mansioni di base
        Mansione::create(['nome' => 'Tiratore']);
        Mansione::create(['nome' => 'Meccanico']);
        Mansione::create(['nome' => 'Medico']);
        Mansione::create(['nome' => 'Cuoco']);
    }

    /**
     * Crea un militare di test con dati predefiniti
     */
    protected function createTestMilitare(array $attributes = []): Militare
    {
        $compagnia = Compagnia::first();
        $defaults = [
            'grado_id' => Grado::where('nome', 'Soldato')->first()->id,
            'plotone_id' => Plotone::first()->id,
            'polo_id' => Polo::first()->id,
            'ruolo_id' => Ruolo::first()->id,
            'mansione_id' => Mansione::first()->id,
            'compagnia_id' => $compagnia->id,
            'nome' => 'Mario',
            'cognome' => 'Rossi'
        ];

        return Militare::create(array_merge($defaults, $attributes));
    }

    /**
     * Crea un utente di test autenticato
     */
    protected function createAuthenticatedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $this->actingAs($user);
        return $user;
    }

    /**
     * Assicura che una risposta sia JSON con una struttura specifica
     */
    protected function assertJsonStructure(array $structure, $response): void
    {
        $response->assertJson($structure);
    }

    /**
     * Crea dati di test per le date
     */
    protected function createTestDates(): array
    {
        return [
            'oggi' => now()->format('Y-m-d'),
            'ieri' => now()->subDay()->format('Y-m-d'),
            'domani' => now()->addDay()->format('Y-m-d'),
            'prossima_settimana' => now()->addWeek()->format('Y-m-d'),
            'mese_scorso' => now()->subMonth()->format('Y-m-d'),
            'prossimo_mese' => now()->addMonth()->format('Y-m-d')
        ];
    }
} 