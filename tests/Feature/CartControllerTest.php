<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_add_product_to_cart()
    {
        // Создаем пользователя и товар
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Авторизуем пользователя
        $this->actingAs($user);

        // Отправляем запрос на добавление товара в корзину
        $response = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
        ]);

        // Проверяем успешный ответ
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Товар добавлен в корзину']);

        // Проверяем, что товар был добавлен в корзину
        $this->assertDatabaseHas('cart_products', [
            'cart_id' => $user->cart->id,
            'product_id' => $product->id,
        ]);
    }

    /** @test */
    public function it_creates_cart_if_not_exists()
    {
        // Создаем пользователя
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Авторизуем пользователя
        $this->actingAs($user);

        // Отправляем запрос на добавление товара в корзину
        $response = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
        ]);

        // Проверяем, что ответ содержит успешное сообщение
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Товар добавлен в корзину']);

        // Проверяем, что корзина была создана
        $this->assertNotNull($user->cart);
    }

    /** @test */
    public function it_returns_404_if_product_not_found()
    {
        // Создаем пользователя
        $user = User::factory()->create();

        // Авторизуем пользователя
        $this->actingAs($user);

        // Отправляем запрос с несуществующим product_id
        $response = $this->postJson('/api/cart/add', [
            'product_id' => 999,  // Не существующий товар
        ]);

        // Проверяем, что товар не найден
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Product not found']);
    }

    /** @test */
    public function it_requires_authentication_to_add_product_to_cart()
    {
        // Создаем товар
        $product = Product::factory()->create();

        // Отправляем запрос без авторизации
        $response = $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
        ]);

        // Проверяем, что запрос требует авторизации
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function it_requires_authentication_to_remove_product_from_cart()
    {
        // Создаем товар
        $product = Product::factory()->create();

        // Отправляем запрос без авторизации
        $response = $this->deleteJson("/api/cart/remove/{$product->id}");

        // Проверяем, что запрос требует авторизации
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function it_can_remove_product_from_cart()
    {
        // Создаем пользователя и товар
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Создаем корзину, если её нет
        $cart = $user->cart()->firstOrCreate([]);

        // Добавляем товар в корзину
        $cart->products()->attach($product);
        // Логинимся и удаляем товар из корзины
        $response = $this->actingAs($user)->deleteJson("/api/cart/remove/{$product->id}");

        $response->assertStatus(200);
        $this->assertCount(0, $user->cart->products); // Проверяем, что товар удален
    }

    /** @test */
    public function it_returns_404_if_cart_not_found()
    {
        // Создаем пользователя
        $user = User::factory()->create();

        // Логинимся
        $this->actingAs($user);

        // Отправляем запрос на удаление товара, при этом у пользователя нет корзины
        $response = $this->deleteJson("/api/cart/remove/999");

        // Проверяем, что корзина не найдена
        $response->assertStatus(404);
        $response->assertJson(['error' => 'Корзина не найдена']);
    }
}
