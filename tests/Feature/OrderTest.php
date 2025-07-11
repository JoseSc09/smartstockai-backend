<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $products;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->products = Product::factory()->count(2)->create(['category_id' => Category::factory()->create()->id, 'stock' => 100]);
    }

    public function test_can_list_orders()
    {
        Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_create_order()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/orders', [
            'products' => [
                ['id' => $this->products[0]->id, 'quantity' => 2],
                ['id' => $this->products[1]->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['user_id' => $this->user->id]);
        $this->assertDatabaseHas('products', ['id' => $this->products[0]->id, 'stock' => 98]);
        $this->assertDatabaseHas('products', ['id' => $this->products[1]->id, 'stock' => 97]);
    }

    public function test_can_show_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['user_id' => $this->user->id]);
    }

    public function test_can_update_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/orders/{$order->id}", [
            'products' => [
                ['id' => $this->products[0]->id, 'quantity' => 1],
            ],
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'completed']);
        $this->assertDatabaseHas('products', ['id' => $this->products[0]->id, 'stock' => 99]);
    }

    public function test_can_delete_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}
