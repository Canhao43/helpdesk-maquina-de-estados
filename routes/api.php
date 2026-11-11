<?php

use App\Http\Controllers\AcaoController;
use App\Http\Controllers\ChamadoController;
use App\Http\Controllers\TokenController;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/tokens/atual', [TokenController::class, 'destroy']);

    Route::get('/eu', fn (Request $request) => (new UsuarioResource($request->user()))
        ->additional(['escopos' => $request->user()->currentAccessToken()->abilities]));

    Route::middleware('escopo:chamados:ler')->group(function () {
        Route::get('/chamados', [ChamadoController::class, 'index'])->name('chamados.index');
        Route::get('/chamados/{chamado}', [ChamadoController::class, 'show'])->name('chamados.show');
        Route::get('/chamados/{chamado}/historico', [ChamadoController::class, 'historico'])->name('chamados.historico');
    });

    Route::post('/chamados', [ChamadoController::class, 'store'])->middleware('escopo:chamados:abrir')->name('chamados.store');

    // {acao} é o enum Acao: valor que não existe vira 404 já na rota.
    Route::post('/chamados/{chamado}/acoes/{acao}', AcaoController::class)
        ->middleware('escopo:chamados:agir')
        ->name('chamados.acoes');
});
