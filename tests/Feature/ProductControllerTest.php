<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_product_by_id()
    {
        // Создаем товар
        $product = Product::factory()->create();

        // Получаем товар по id
        $response = $this->getJson('/api/products/' . $product->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'price'
        ]);
    }

    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_product()
    {
        // Создаем пользователя (предположим, что создание продукта доступно только авторизованным пользователям)
        $user = User::factory()->create();

        // Логинимся
        $this->actingAs($user);

        // Отправляем запрос на создание продукта
        $response = $this->postJson('/api/products', [
            'name' => 'Test Product',
            'price' => 100
        ]);

        // Проверяем, что продукт был создан
        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'Test Product',
            'price' => 100
        ]);

        // Проверяем, что продукт появился в базе данных
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'price' => 100
        ]);
    }

    /** @test */
    public function it_returns_422_if_required_fields_are_missing()
    {
        // Создаем пользователя
        $user = User::factory()->create();

        // Логинимся
        $this->actingAs($user);

        // Отправляем запрос с отсутствующими обязательными полями
        $response = $this->postJson('/api/products', []);

        // Проверяем, что возвращен статус 422, а не 500
        $response->assertStatus(422);  // Изменен с 500 на 422
        $response->assertJsonValidationErrors(['name', 'price']);
    }
    /** @test */
    public function it_can_get_list_of_products()
    {
        // Создаем несколько товаров
        Product::factory()->count(5)->create();

        // Получаем список товаров
        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(5); // Проверяем, что в ответе 5 товаров
    }

}
