<?php

use App\Http\Controllers\TokenController;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/tokens/atual', [TokenController::class, 'destroy']);

    Route::get('/eu', fn (Request $request) => new UsuarioResource($request->user()));
});
