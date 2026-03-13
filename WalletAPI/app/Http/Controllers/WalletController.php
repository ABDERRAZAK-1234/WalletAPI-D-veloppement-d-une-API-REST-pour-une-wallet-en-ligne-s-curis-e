<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * GET /api/wallets
     */
    public function index(Request $request)
    {
        $wallets = $request->user()->wallets()->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des wallets récupérée.',
            'data' => [
                'wallets' => $wallets
            ]
        ]);
    }

    /**
     * POST /api/wallets
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'currency' => 'required|in:MAD,EUR,USD'
            ],
            [
                'name.required' => 'Le nom du wallet est obligatoire.',
                'currency.in' => "La devise sélectionnée n'est pas valide."
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors()
            ], 422);
        }

        $wallet = $request->user()->wallets()->create([
            'name' => $request->name,
            'currency' => $request->currency,
            'balance' => 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet créé avec succès.',
            'data' => [
                'wallet' => $wallet
            ]
        ], 201);
    }

    /**
     * GET /api/wallets/{id}
     */
    public function show(Request $request, Wallet $wallet)
    {

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à accéder à ce wallet."
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détail du wallet récupéré.',
            'data' => [
                'wallet' => $wallet
            ]
        ]);
    }

    /**
     * DELETE /api/wallets/{id}
     */
    public function destroy(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'êtes pas autorisé à supprimer ce wallet."
            ], 403);
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet supprimé avec succès.'
        ]);
    }
}
