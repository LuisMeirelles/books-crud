<?php

namespace Database\Factories;

use App\Models\Livro;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LivroFactory extends Factory
{
    protected $model = Livro::class;

    public function definition(): array
    {
        return [
            'titulo' => $this->faker->word(),
            'usuario_publicador_id' => User::factory(),
        ];
    }
}
