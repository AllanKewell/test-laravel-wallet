<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Wallet;

use function Pest\Laravel\actingAs;

test('dashboard page is displayed', function () {
    $user = User::factory()->has(Wallet::factory()->richChillGuy())->create();
    $wallet = Wallet::factory()->richChillGuy()->for($user)->create();

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeTextInOrder([
            __('Balance'),
            Number::currencyCents($wallet->balance),
            'Transactions history',
            'Just a rich chill guy',
        ]);
});

test("dashboard page but user haven't wallet", function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get('/');

    $response->assertServerError();
});

test('send money to a friend', function () {
    $user = User::factory()->has(Wallet::factory()->richChillGuy())->create();
    $recipient = User::factory()->has(Wallet::factory())->create();

    $response = actingAs($user)->post('/send-money', [
        'recipient_email' => $recipient->email,
        'amount' => 10, // In euros, not cents
        'reason' => 'Just a chill guy gift',
    ]);

    $response
        ->assertRedirect('/')
        ->assertSessionHas('money-sent-status', 'success')
        ->assertSessionHas('money-sent-recipient-name', $recipient->name)
        ->assertSessionHas('money-sent-amount', 10_00);

    actingAs($user)->get('/')
        ->assertSeeTextInOrder([
            __('Balance'),
            Number::currencyCents(1_000_000 - 10_00),
            'Transactions history',
            'Just a chill guy gift',
            Number::currencyCents(-10_00),
            'Just a rich chill guy',
            Number::currencyCents(1_000_000),
        ]);
});

test('cannot send money to a friend with insufficient balance', function () {
    $user = User::factory()->has(Wallet::factory())->create();
    $recipient = User::factory()->has(Wallet::factory())->create();

    $response = actingAs($user)->post('/send-money', [
        'recipient_email' => $recipient->email,
        'amount' => 10, // In euros, not cents
        'reason' => 'Just a chill guy gift',
    ]);

    $response
        ->assertRedirect('/')
        ->assertSessionHas('money-sent-status', 'insufficient-balance')
        ->assertSessionHas('money-sent-recipient-name', $recipient->name)
        ->assertSessionHas('money-sent-amount', 10_00);
});
