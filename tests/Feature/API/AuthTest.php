<?php

use App\Models\User;

test('usuário pode gerar um token de API', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123')
    ]);

    $response = $this->postJson('/v1/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['token']);
});

test('autenticação falha com credenciais inválidas', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123')
    ]);

    $this->postJson('/v1/auth/token', [
        'email' => 'test@example.com',
        'password' => 'senha_errada',
    ])->assertStatus(401);

    $this->postJson('/v1/auth/token', [
        'email' => 'email_errado@example.com',
        'password' => 'password123',
    ])->assertStatus(401);
});

test('requisição falha quando campos obrigatórios estão ausentes', function () {
    $this->postJson('/v1/auth/token', [
        'password' => 'password123',
    ])->assertStatus(422);
    $this->postJson('/v1/auth/token', [
        'email' => 'test@example.com',
    ])->assertStatus(422);
});

test('token gerado permite acesso a rotas protegidas', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer ' . $token)
         ->getJson('/v1/livros')
         ->assertStatus(200);
});

test('não autenticado não pode acessar rotas protegidas', function () {
    $this->getJson('/v1/livros')
         ->assertStatus(401);

    $this->postJson('/v1/livros')
         ->assertStatus(401);
});
