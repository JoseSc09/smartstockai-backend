<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        $products = Product::factory()->count(2)->create();
        $total = $products->sum('price') * $this->faker->numberBetween(1, 5);

        return [
            'user_id' => User::factory(),
            'total' => $total,
            'status' => 'pending',
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            $products = Product::factory()->count(2)->create();
            $syncData = [];
            foreach ($products as $product) {
                $quantity = $this->faker->numberBetween(1, 5);
                $syncData[$product->id] = ['quantity' => $quantity, 'price' => $product->price];
                $product->stock -= $quantity;
                $product->save();
            }
            $order->products()->sync($syncData);
        });
    }
}
