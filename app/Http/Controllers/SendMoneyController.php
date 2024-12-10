<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PerformWalletTransfer;
use App\Exceptions\InsufficientBalance;
use App\Http\Requests\SendMoneyRequest;
use App\Mail\BalanceLow;
use Illuminate\Support\Facades\Mail;

class SendMoneyController
{
    public function __invoke(SendMoneyRequest $request, PerformWalletTransfer $performWalletTransfer)
    {
        $recipient = $request->getRecipient();

        try {
            $performWalletTransfer->execute(
                sender: $request->user(),
                recipient: $recipient,
                amount: $request->getAmountInCents(),
                reason: $request->input('reason'),
            );

            // If sender have balance less of 10€ after transaction, send email for prevent
            if ($request->user()->balance < 10) {
                Mail::to($request->user())->send(new BalanceLow($request->user()->wallet));
            }

            return redirect()->back()
                ->with('money-sent-status', 'success')
                ->with('money-sent-recipient-name', $recipient->name)
                ->with('money-sent-amount', $request->getAmountInCents());
        } catch (InsufficientBalance $exception) {
            return redirect()->back()->with('money-sent-status', 'insufficient-balance')
                ->with('money-sent-recipient-name', $recipient->name)
                ->with('money-sent-amount', $request->getAmountInCents());
        }
    }
}
