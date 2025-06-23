<?php

namespace Database\Factories;

use App\Models\Militare;
use App\Models\Grado;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;
use Illuminate\Database\Eloquent\Factories\Factory;

class MilitareFactory extends Factory
{
    protected $model = Militare::class;

    public function definition(): array
    {
        return [
            'grado_id' => function () {
                return Grado::inRandomOrder()->first()->id;
            },
            'cognome' => $this->faker->lastName(),
            'nome' => $this->faker->firstName(),
            'plotone_id' => function () {
                return Plotone::inRandomOrder()->first()->id;
            },
            'polo_id' => function () {
                return Polo::inRandomOrder()->first()->id;
            },
            'ruolo_id' => function () {
                return Ruolo::inRandomOrder()->first()->id;
            },
            'mansione_id' => function () {
                return Mansione::inRandomOrder()->first()->id;
            },
            'certificati_note' => $this->faker->optional()->sentence(),
            'idoneita_note' => $this->faker->optional()->sentence(),
        ];
    }
} 
