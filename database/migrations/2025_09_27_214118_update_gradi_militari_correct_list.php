<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disabilita temporaneamente i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Pulisce la tabella gradi
        DB::table('gradi')->truncate();
        
        // Inserisce i 23 gradi corretti in ordine gerarchico (dal più alto al più basso)
        $gradi = [
            // Ufficiali
            ['nome' => 'Colonnello', 'sigla' => 'Col.', 'categoria' => 'Ufficiali', 'ordine' => 1],
            ['nome' => 'Tenente Colonnello', 'sigla' => 'Ten. Col.', 'categoria' => 'Ufficiali', 'ordine' => 2],
            ['nome' => 'Maggiore', 'sigla' => 'Magg.', 'categoria' => 'Ufficiali', 'ordine' => 3],
            ['nome' => 'Capitano', 'sigla' => 'Cap.', 'categoria' => 'Ufficiali', 'ordine' => 4],
            ['nome' => 'Tenente', 'sigla' => 'Ten.', 'categoria' => 'Ufficiali', 'ordine' => 5],
            
            // Sottufficiali
            ['nome' => 'Primo Luogotenente', 'sigla' => '1° Lgt.', 'categoria' => 'Sottufficiali', 'ordine' => 6],
            ['nome' => 'Luogotenente', 'sigla' => 'Lgt.', 'categoria' => 'Sottufficiali', 'ordine' => 7],
            ['nome' => 'Primo Maresciallo', 'sigla' => '1° Mar.', 'categoria' => 'Sottufficiali', 'ordine' => 8],
            ['nome' => 'Maresciallo Capo', 'sigla' => 'Mar. Ca.', 'categoria' => 'Sottufficiali', 'ordine' => 9],
            ['nome' => 'Maresciallo Ordinario', 'sigla' => 'Mar. Ord.', 'categoria' => 'Sottufficiali', 'ordine' => 10],
            ['nome' => 'Maresciallo', 'sigla' => 'Mar.', 'categoria' => 'Sottufficiali', 'ordine' => 11],
            ['nome' => 'Sergente Maggiore Aiutante', 'sigla' => 'Serg. Magg. Aiut.', 'categoria' => 'Sottufficiali', 'ordine' => 12],
            ['nome' => 'Sergente Maggiore Capo', 'sigla' => 'Serg. Magg. Ca.', 'categoria' => 'Sottufficiali', 'ordine' => 13],
            ['nome' => 'Sergente Maggiore', 'sigla' => 'Serg. Magg.', 'categoria' => 'Sottufficiali', 'ordine' => 14],
            ['nome' => 'Sergente', 'sigla' => 'Serg.', 'categoria' => 'Sottufficiali', 'ordine' => 15],
            
            // Graduati
            ['nome' => 'Graduato Aiutante', 'sigla' => 'Grd. A.', 'categoria' => 'Graduati', 'ordine' => 16],
            ['nome' => 'Primo Graduato', 'sigla' => '1° Grd.', 'categoria' => 'Graduati', 'ordine' => 17],
            ['nome' => 'Graduato Capo', 'sigla' => 'Grd. Ca.', 'categoria' => 'Graduati', 'ordine' => 18],
            ['nome' => 'Graduato Scelto', 'sigla' => 'Grd. Sc.', 'categoria' => 'Graduati', 'ordine' => 19],
            ['nome' => 'Graduato', 'sigla' => 'Grd.', 'categoria' => 'Graduati', 'ordine' => 20],
            
            // Truppa
            ['nome' => 'Caporal Maggiore', 'sigla' => 'C.le Magg.', 'categoria' => 'Truppa', 'ordine' => 21],
            ['nome' => 'Caporale', 'sigla' => 'C.le', 'categoria' => 'Truppa', 'ordine' => 22],
            ['nome' => 'Soldato', 'sigla' => 'Sol.', 'categoria' => 'Truppa', 'ordine' => 23],
        ];
        
        foreach ($gradi as $grado) {
            DB::table('gradi')->insert([
                'nome' => $grado['nome'],
                'sigla' => $grado['sigla'],
                'categoria' => $grado['categoria'],
                'ordine' => $grado['ordine'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Riabilita i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disabilita temporaneamente i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Pulisce la tabella gradi
        DB::table('gradi')->truncate();
        
        // Riabilita i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};