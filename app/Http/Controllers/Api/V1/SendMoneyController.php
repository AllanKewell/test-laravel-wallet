<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\PerformWalletTransfer;
use App\Http\Requests\Api\V1\SendMoneyRequest;
use App\Mail\BalanceLow;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class SendMoneyController
{
    public function __invoke(SendMoneyRequest $request, PerformWalletTransfer $performWalletTransfer): Response
    {
        $recipient = $request->getRecipient();
        $sender = $request->user();

        $performWalletTransfer->execute(
            sender: $request->user(),
            recipient: $recipient,
            amount: $request->input('amount'),
            reason: $request->input('reason'),
        );

        // If sender have balance less of 10€ after transaction, send email for prevent
        if ($sender->balance < 10) {
            Mail::to($sender)->send(new BalanceLow($sender->wallet));
        }

        return response()->noContent(201);
    }
}
