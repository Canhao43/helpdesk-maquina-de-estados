<?php

namespace App\Http\Controllers;

use App\Enums\Escopo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    private const VALIDADE_EM_DIAS = 7;

    private const TEMPO_MINIMO_DA_FALHA_EM_MICROSSEGUNDOS = 200_000;

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
            // Sem escopos, o token pode tudo o que o papel do dono deixa. Com
            // escopos, só aquilo: um sistema que só abre chamado não precisa
            // de um token que lê a fila inteira.
            'escopos' => ['nullable', 'array', 'min:1'],
            'escopos.*' => ['distinct', Rule::enum(Escopo::class)],
        ]);

        // E-mail inexistente e senha errada dão a mesma resposta, e no mesmo
        // tempo: sem conta, o hash não roda e a falha voltaria mais rápido,
        // entregando pelo relógio o que a mensagem esconde. A falha leva no
        // mínimo o tempo da caixa; o acerto sai na hora. Igual ao login de
        // sessão do próprio Laravel (SessionGuard).
        $usuario = (new Timebox)->call(function (Timebox $timebox) use ($dados) {
            $usuario = User::where('email', $dados['email'])->first();

            if (! $usuario || ! Hash::check($dados['senha'], $usuario->password)) {
                throw ValidationException::withMessages([
                    'email' => 'E-mail ou senha incorretos.',
                ]);
            }

            $timebox->returnEarly();

            return $usuario;
        }, self::TEMPO_MINIMO_DA_FALHA_EM_MICROSSEGUNDOS);

        // Token sem validade é credencial eterna: se vazar, vale até alguém
        // lembrar de revogar. Sete dias pedem um login por semana.
        $token = $usuario->createToken(
            $dados['dispositivo'] ?? 'api',
            $dados['escopos'] ?? ['*'],
            now()->addDays(self::VALIDADE_EM_DIAS),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'escopos' => $token->accessToken->abilities,
            'expira_em' => $token->accessToken->expires_at?->toIso8601String(),
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
