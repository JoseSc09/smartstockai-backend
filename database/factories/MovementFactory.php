<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Movement;
use App\Models\Product;

class MovementFactory extends Factory
{
    protected $model = Movement::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'type' => $this->faker->randomElement(['in', 'out']),
            'quantity' => $this->faker->numberBetween(1, 50),
        ];
    }
}
