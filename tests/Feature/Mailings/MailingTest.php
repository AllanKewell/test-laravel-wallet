<?php

declare(strict_types=1);

use App\Mail\BalanceLow;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Mail;

test('mailable content of balance low', function () {
    $user = User::factory()->has(Wallet::factory())->create();

    $mailable = new BalanceLow($user->wallet);

    $mailable->assertSeeInHtml($user->wallet->balance);
});

test('balance of wallet is less of 10€', function () {
    $user = User::factory()->has(Wallet::factory())->create();

    Mail::fake();

    // Assert that no mailables were sent...
    Mail::assertNothingSent();
});
