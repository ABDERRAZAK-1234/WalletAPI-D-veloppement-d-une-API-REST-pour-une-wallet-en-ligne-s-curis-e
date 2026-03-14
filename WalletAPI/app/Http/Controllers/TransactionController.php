<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function deposit(Request $request, Wallet $wallet)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['success'=>false,'message'=>"Vous n'êtes pas autorisé."], 403);
        }

        $transaction = DB::transaction(function () use ($wallet, $request) {
            $wallet->increment('balance', $request->amount);

            return Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_DEPOSIT,
                'amount' => $request->amount,   
                'description' => $request->description,
                'balance_after' => $wallet->balance
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Dépôt effectué avec succès.",
            'data' => [
                'transaction' => $transaction,
                'wallet' => [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'currency' => $wallet->currency,
                    'balance' => $wallet->balance,
                ]
            ]
        ]);
    }

    public function withdraw(Request $request, Wallet $wallet)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['success'=>false,'message'=>"Vous n'êtes pas autorisé."], 403);
        }

        if ($wallet->balance < $request->amount) {
            return response()->json([
                'success'=>false,
                'message'=>"Solde insuffisant. Solde actuel : {$wallet->balance} {$wallet->currency}."
            ], 400);
        }

        $transaction = DB::transaction(function () use ($wallet, $request) {
            $wallet->decrement('balance', $request->amount);

            return Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => Transaction::TYPE_WITHDRAW,
                'amount' => $request->amount,
                'description' => $request->description,
                'balance_after' => $wallet->balance
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Retrait effectué avec succès.",
            'data' => [
                'transaction' => $transaction,
                'wallet' => [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'currency' => $wallet->currency,
                    'balance' => $wallet->balance,
                ]
            ]
        ]);
    }

    public function transfer(Request $request, Wallet $wallet)
    {
        $request->validate([
            'receiver_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $toWallet = Wallet::find($request->receiver_wallet_id);

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['success'=>false,'message'=>"Vous n'êtes pas autorisé."], 403);
        }

        if (!$toWallet) {
            return response()->json(['success'=>false,'message'=>"Le wallet destinataire est introuvable."], 404);
        }

        if ($wallet->currency !== $toWallet->currency) {
            return response()->json(['success'=>false,'message'=>"Transfert impossible : les deux wallets doivent avoir la même devise."], 400);
        }

        if ($wallet->balance < $request->amount) {
            return response()->json([
                'success'=>false,
                'message'=>"Solde insuffisant. Solde actuel : {$wallet->balance} {$wallet->currency}."
            ], 400);
        }

        DB::transaction(function () use ($wallet, $toWallet, $request, &$transactionOut, &$transactionIn) {
            $wallet->decrement('balance', $request->amount);
            $toWallet->increment('balance', $request->amount);

            $transactionOut = Transaction::create([
                'wallet_id' => $wallet->id,
                'related_wallet_id' => $toWallet->id,
                'type' => 'transfer_out',
                'amount' => $request->amount,
                'description' => $request->description,
                'balance_after' => $wallet->balance
            ]);

            $transactionIn = Transaction::create([
                'wallet_id' => $toWallet->id,
                'related_wallet_id' => $wallet->id,
                'type' => 'transfer_in',
                'amount' => $request->amount,
                'description' => $request->description,
                'balance_after' => $wallet->balance
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Transfert effectué avec succès.",
            'data' => [
                'transaction_out' => $transactionOut,
                'transaction_in' => $transactionIn,
                'wallet' => [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'currency' => $wallet->currency,
                    'balance' => $wallet->balance,
                ]
            ]
        ]);
    }

    public function history(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['success'=>false,'message'=>"Vous n'êtes pas autorisé."], 403);
        }

        $transactions = $wallet->transactions()->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => "Historique des transactions récupéré.",
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ]
            ]
        ]);
    }
}
