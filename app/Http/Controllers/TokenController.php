<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    private const VALIDADE_EM_DIAS = 7;

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
        ]);

        $usuario = User::where('email', $dados['email'])->first();

        // E-mail inexistente e senha errada dão a mesma resposta: quem tenta
        // não descobre quais e-mails têm conta.
        if (! $usuario || ! Hash::check($dados['senha'], $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos.',
            ]);
        }

        // Token sem validade é credencial eterna: se vazar, vale até alguém
        // lembrar de revogar. Sete dias pedem um login por semana.
        $token = $usuario->createToken(
            $dados['dispositivo'] ?? 'api',
            ['*'],
            now()->addDays(self::VALIDADE_EM_DIAS),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'expira_em' => $token->accessToken->expires_at?->toIso8601String(),
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
