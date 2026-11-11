<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['escopo' => CheckAbilities::class]);

        // O escopo é checado antes de o chamado ser buscado pelo id. Na ordem
        // padrão, a busca vem antes: um token sem leitura receberia 404 num
        // id inexistente e 403 num existente, e a diferença diria quais
        // chamados existem.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: CheckAbilities::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Todo 404 da API sai com o mesmo corpo. O padrão do Laravel diria o
        // nome do model ("No query results for model...") no chamado que não
        // existe e "Not Found" no chamado de outra pessoa: a diferença na
        // mensagem entregaria o que o 404 da policy quer esconder.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 404 && $request->is('api/*')) {
                return response()->json(['message' => 'Não encontrado.'], 404);
            }
        });
    })->create();
