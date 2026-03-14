<?php

// use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('auth:sanctum')->get('/profile', [AuthController::class, 'profile']);
});


Route::middleware('auth:sanctum')->group(function(){
    // wallet
    Route::post('/wallets',[WalletController::class,'store']);
    Route::get('/wallets',[WalletController::class,'index']);
    Route::get('/wallets/{wallet}',[WalletController::class,'show']);
    Route::delete('/wallets/{wallet}',[WalletController::class,'destroy']);

    // Transactions
    Route::post('/wallets/{wallet}/deposit', [TransactionController::class, 'deposit']);
    Route::post('/wallets/{wallet}/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/wallets/{wallet}/transfer', [TransactionController::class, 'transfer']);
    Route::get('/wallets/{wallet}/transactions', [TransactionController::class, 'history']);

});
