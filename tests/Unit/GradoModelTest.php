<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Grado;
use App\Models\Militare;

/**
 * Test per il modello Grado
 */
class GradoModelTest extends TestCase
{
    /**
     * Test creazione grado con dati validi
     */
    public function test_grado_creation_with_valid_data(): void
    {
        $grado = Grado::create([
            'nome' => 'Test Grado',
            'ordine' => 15
        ]);

        $this->assertDatabaseHas('gradi', [
            'nome' => 'Test Grado',
            'ordine' => 15
        ]);

        $this->assertEquals('Test Grado', $grado->nome);
        $this->assertEquals(15, $grado->ordine);
    }

    /**
     * Test relazione con militari
     */
    public function test_grado_has_many_militari(): void
    {
        $this->seedBasicData();
        
        $grado = Grado::where('nome', 'Generale')->first();
        $militare = $this->createTestMilitare([
            'grado_id' => $grado->id,
            'nome' => 'Test Generale'
        ]);

        $this->assertTrue($grado->militari->contains($militare));
        $this->assertInstanceOf(Militare::class, $grado->militari->first());
    }

    /**
     * Test scope per ordinamento
     */
    public function test_scope_per_ordine(): void
    {
        // Pulisci i gradi esistenti per questo test
        \App\Models\Grado::query()->delete();
        
        // Crea gradi con ordini diversi
        $grado1 = Grado::create(['nome' => 'Primo', 'ordine' => 1]);
        $grado2 = Grado::create(['nome' => 'Secondo', 'ordine' => 2]);
        $grado3 = Grado::create(['nome' => 'Terzo', 'ordine' => 3]);

        $gradi = Grado::perOrdine()->get();

        // Con orderByDesc('ordine'), ordine più alto viene primo
        $this->assertEquals('Terzo', $gradi->first()->nome);
        $this->assertEquals('Primo', $gradi->last()->nome);
    }

    /**
     * Test scope per nome
     */
    public function test_scope_per_nome(): void
    {
        Grado::create(['nome' => 'Zebra', 'ordine' => 1]);
        Grado::create(['nome' => 'Alpha', 'ordine' => 2]);
        Grado::create(['nome' => 'Beta', 'ordine' => 3]);

        $gradi = Grado::perNome()->get();

        // Dovrebbero essere ordinati alfabeticamente
        $this->assertEquals('Alpha', $gradi->first()->nome);
        $this->assertEquals('Zebra', $gradi->last()->nome);
    }

    /**
     * Test conteggio militari per grado
     */
    public function test_conteggio_militari_per_grado(): void
    {
        $this->seedBasicData();
        
        $grado = Grado::where('nome', 'Soldato')->first();
        
        // Crea 3 militari con questo grado
        for ($i = 0; $i < 3; $i++) {
            $this->createTestMilitare([
                'grado_id' => $grado->id,
                'nome' => "Soldato $i"
            ]);
        }

        $this->assertEquals(3, $grado->militari()->count());
    }

    /**
     * Test ordinamento gradi per importanza
     */
    public function test_ordinamento_per_importanza(): void
    {
        $this->seedBasicData();
        
        $gradi = Grado::orderBy('ordine', 'asc')->get();
        
        $generale = $gradi->where('nome', 'Generale')->first();
        $soldato = $gradi->where('nome', 'Soldato')->first();
        
        // Il generale dovrebbe avere ordine più basso (più importante)
        $this->assertLessThan($soldato->ordine, $generale->ordine);
    }

    /**
     * Test che i gradi possono avere nomi diversi
     */
    public function test_gradi_con_nomi_diversi(): void
    {
        $grado1 = Grado::create([
            'nome' => 'Grado Uno',
            'ordine' => 99
        ]);
        
        $grado2 = Grado::create([
            'nome' => 'Grado Due',
            'ordine' => 100
        ]);
        
        $this->assertNotEquals($grado1->nome, $grado2->nome);
        $this->assertEquals('Grado Uno', $grado1->nome);
        $this->assertEquals('Grado Due', $grado2->nome);
    }

    /**
     * Test fillable attributes
     */
    public function test_fillable_attributes(): void
    {
        $grado = new Grado();
        
        $expected = ['nome', 'ordine'];
        $this->assertEquals($expected, $grado->getFillable());
    }
} 