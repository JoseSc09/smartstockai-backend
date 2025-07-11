<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Movement;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'stock' => 100
        ]);
        Movement::factory()->create([
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => 50
        ]);
    }

    public function test_can_query_top_selling_product()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/chatbot', [
            'query' => 'producto más vendido',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['response' => "El producto más vendido es {$this->product->name} con 50 unidades vendidas."]);
    }

    public function test_can_query_product_stock()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/chatbot', [
            'query' => "stock de {$this->product->name}",
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['response' => "El stock de {$this->product->name} es {$this->product->stock} unidades."]);
    }

    public function test_handles_invalid_query()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/chatbot', [
            'query' => 'consulta inválida',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['response' => 'Lo siento, no entiendo la consulta. Prueba con "producto más vendido" o "stock de [nombre]".']);
    }
}
