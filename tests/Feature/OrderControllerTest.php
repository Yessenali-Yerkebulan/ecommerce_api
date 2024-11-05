<?php

namespace Tests\Feature;

use App\Jobs\UpdateOrderStatusJob;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_order_and_generate_payment_url()
    {
        // Создаем тестового пользователя
        $user = User::factory()->create();

        // Создаем способ оплаты
        $paymentMethod = PaymentMethod::factory()->create();

        // Создаем товары
        $product = Product::factory()->create();

        // Создаем корзину, если её еще нет
        $cart = $user->cart()->firstOrCreate([]);

        // Добавляем товар в корзину пользователя
        $cart->products()->attach($product);

        // Делаем запрос на создание заказа
        $response = $this->actingAs($user)->postJson('/api/cart/checkout', [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // Проверяем, что заказ был создан и возвращена правильная информация
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'payment_url',
            'order' => [
                'id',
                'user_id',
                'payment_method_id',
                'status',
                'created_at',
                'updated_at',
            ]
        ]);

        // Проверка, что заказ имеет статус "На оплату"
        $order = Order::latest()->first();
        $this->assertEquals('На оплату', $order->status);
        $this->assertNotNull($order->payment_method_id);
        $this->assertEquals($paymentMethod->id, $order->payment_method_id);
    }

    /** @test */
    public function it_can_update_order_status_to_paid()
    {
        // Создаем пользователя
        $user = User::factory()->create();

        // Логинимся
        $this->actingAs($user);

        // Создаем заказ
        $order = Order::factory()->create(['status' => 'На оплату']);

        // Имитация перехода по ссылке на оплату
        $response = $this->postJson("/api/orders/update-status/{$order->id}");

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('Оплачен', $order->status);
    }

    /** @test */
    public function it_can_get_user_orders()
    {
        // Создаем пользователя и заказы
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        // Получаем заказы пользователя
        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertStatus(200);
        $response->assertJsonCount(3); // Проверяем, что вернуло 3 заказа
    }

    /** @test */
    public function it_can_filter_orders_by_status()
    {
        // Создаем пользователя и заказы
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id, 'status' => 'Оплачен']);
        Order::factory()->create(['user_id' => $user->id, 'status' => 'На оплату']);

        // Получаем заказы со статусом "На оплату"
        $response = $this->actingAs($user)->getJson('/api/orders?status=На оплату');

        $response->assertStatus(200);
        $response->assertJsonCount(1); // Проверяем, что вернулся только 1 заказ с нужным статусом
    }

    /** @test */
    public function it_can_sort_orders_by_creation_date()
    {
        // Создаем пользователя
        $user = User::factory()->create();

        // Создаем несколько заказов с разными датами создания
        $order1 = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(2)]);
        $order2 = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(1)]);
        $order3 = Order::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

        // Логинимся как пользователь
        $this->actingAs($user);

        // Запрос на получение заказов, сортированных по дате создания (по убыванию)
        $response = $this->getJson('/api/orders?created_at=desc');

        // Проверяем статус ответа
        $response->assertStatus(200);

        // Проверяем, что заказы возвращаются в правильном порядке
        $orders = $response->json();

        // Заказы должны быть отсортированы по дате создания в порядке убывания
        $this->assertTrue($orders[0]['created_at'] >= $orders[1]['created_at']);
        $this->assertTrue($orders[1]['created_at'] >= $orders[2]['created_at']);

        // Проверяем, что правильные заказы пришли в ответе
        $this->assertEquals($order3->id, $orders[0]['id']);  // самый новый заказ
        $this->assertEquals($order2->id, $orders[1]['id']);  // второй по времени
        $this->assertEquals($order1->id, $orders[2]['id']);  // самый старый заказ
    }
}
