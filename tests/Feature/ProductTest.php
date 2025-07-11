<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->category = Category::factory()->create();
    }

    public function test_can_list_products()
    {
        Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_create_product()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/products', [
            'name' => 'Producto 1',
            'description' => 'Descripción del producto',
            'price' => 99.99,
            'stock' => 100,
            'category_id' => $this->category->id,
            'low_stock_threshold' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Producto 1']);
    }

    public function test_can_show_product()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $product->name]);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/products/{$product->id}", [
            'name' => 'Producto Actualizado',
            'description' => 'Nueva descripción',
            'price' => 149.99,
            'stock' => 50,
            'category_id' => $this->category->id,
            'low_stock_threshold' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Producto Actualizado']);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
