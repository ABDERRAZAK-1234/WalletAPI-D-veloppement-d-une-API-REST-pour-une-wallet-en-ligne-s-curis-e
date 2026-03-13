<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|confirmed',
            ],
            [
                'email.unique' => "L'adresse email est déjà utilisée.",
                'password.min' => "Le mot de passe doit contenir au moins 8 caractères."
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "success" => true,
            "message" => "Inscription réussie.",
            "data" => [
                "user" => $user,
                'token' => $token,
            ]
        ]);
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Identifiants incorrects.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "success" => true,
            "message" => "Connexion réussie.",
            "data" => [
                "user" => $user,
                'token' => $token,
            ]
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success'=>true,
            'message' => 'Déconnexion réussie.'
        ]);
    }

    // show profile
    public function profile(Request $request)
{
    return response()->json([
        "success" => true,
        "message" => "Profil utilisateur récupéré.",
        "data" => [
            "user" => $request->user()
        ]
    ]);
}
}
