<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    public function test_can_list_categories()
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_category()
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/categories', [
            'name' => 'Electrónica',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Electrónica']);
    }

    public function test_can_show_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $category->name]);
    }

    public function test_can_update_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/categories/{$category->id}", [
            'name' => 'Ropa',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Ropa']);
    }

    public function test_can_delete_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
