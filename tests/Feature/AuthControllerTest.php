<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_register_new_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
        ];

        // Отправляем запрос на регистрацию
        $response = $this->postJson('/api/register', $userData);

        // Проверяем успешный ответ и наличие токена
        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);

        // Проверяем, что пользователь был создан в базе данных
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
        ]);
    }

    /** @test */
    public function it_cannot_register_user_with_existing_email()
    {
        // Создаем пользователя для проверки
        $existingUser = User::factory()->create();

        $userData = [
            'name' => 'John Doe',
            'email' => $existingUser->email, // Используем уже существующий email
            'password' => 'password123',
        ];

        // Отправляем запрос на регистрацию
        $response = $this->postJson('/api/register', $userData);

        // Проверяем, что ответ содержит ошибку
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email'); // Проверка ошибки для поля email
    }

    /** @test */
    public function it_can_authenticate_user()
    {
        // Создаем пользователя
        $user = User::factory()->create();

        // Делаем запрос для авторизации
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password' // Предположим, что пароль 'password' для фабрики
        ]);

        // Проверяем успешный ответ
        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);  // Предположим, что вы возвращаете токен
    }
}
