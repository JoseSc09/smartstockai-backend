<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Movement;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'stock' => 100]);
    }

    public function test_can_list_movements()
    {
        Movement::factory()->create(['product_id' => $this->product->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/movements');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_create_movement()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['quantity' => 10]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 110]);
    }

    public function test_can_show_movement()
    {
        $movement = Movement::factory()->create(['product_id' => $this->product->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/movements/{$movement->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['quantity' => $movement->quantity]);
    }

    public function test_can_update_movement()
    {
        $movement = Movement::factory()->create(['product_id' => $this->product->id, 'type' => 'in', 'quantity' => 10]);

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/movements/{$movement->id}", [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['quantity' => 5]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 95]);
    }

    public function test_can_delete_movement()
    {
        $movement = Movement::factory()->create(['product_id' => $this->product->id, 'type' => 'out', 'quantity' => 10]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/movements/{$movement->id}");

        $response->assertStatus(204);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 110]);
    }
}
