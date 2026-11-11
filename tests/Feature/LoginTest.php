<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correto_devolve_token_que_funciona(): void
    {
        $usuario = User::factory()->atendente()->create(['email' => 'ana@exemplo.test']);

        $resposta = $this->postJson('/api/tokens', [
            'email' => 'ana@exemplo.test',
            'senha' => 'password',
            'dispositivo' => 'teste',
        ]);

        $resposta->assertCreated()->assertJsonPath('tipo', 'Bearer');
        $this->assertNotNull($resposta->json('expira_em'));

        $this->withToken($resposta->json('token'))
            ->getJson('/api/eu')
            ->assertOk()
            ->assertJsonPath('data.id', $usuario->id)
            ->assertJsonPath('data.papel', 'atendente');
    }

    public function test_senha_errada_e_email_inexistente_dao_a_mesma_resposta(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.test']);

        $senhaErrada = $this->postJson('/api/tokens', ['email' => 'ana@exemplo.test', 'senha' => 'errada']);
        $semConta = $this->postJson('/api/tokens', ['email' => 'ninguem@exemplo.test', 'senha' => 'errada']);

        $senhaErrada->assertUnprocessable();
        $semConta->assertUnprocessable();
        $this->assertSame($senhaErrada->json(), $semConta->json());
    }

    public function test_falha_de_login_leva_o_tempo_minimo_com_ou_sem_conta(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.test']);

        // Limite de baixo, e não comparação entre os dois tempos: é o que dá
        // pra garantir sem o teste ficar instável.
        foreach (['ana@exemplo.test', 'ninguem@exemplo.test'] as $email) {
            $inicio = microtime(true);
            $this->postJson('/api/tokens', ['email' => $email, 'senha' => 'errada'])->assertUnprocessable();
            $this->assertGreaterThanOrEqual(0.2, microtime(true) - $inicio, "Falha rápida demais para {$email}");
        }
    }

    public function test_sem_token_nao_entra(): void
    {
        $this->getJson('/api/eu')->assertUnauthorized();
    }

    public function test_token_vencido_nao_entra(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.test']);
        $token = $this->postJson('/api/tokens', ['email' => 'ana@exemplo.test', 'senha' => 'password'])->json('token');

        $this->travel(8)->days();

        $this->withToken($token)->getJson('/api/eu')->assertUnauthorized();
    }

    public function test_sexta_tentativa_no_mesmo_minuto_e_barrada(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/tokens', ['email' => 'ana@exemplo.test', 'senha' => 'errada'])->assertUnprocessable();
        }

        // Nem a senha certa passa enquanto o limite estiver estourado.
        $this->postJson('/api/tokens', ['email' => 'ana@exemplo.test', 'senha' => 'password'])->assertTooManyRequests();
    }

    public function test_sair_revoga_o_token(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.test']);
        $token = $this->postJson('/api/tokens', ['email' => 'ana@exemplo.test', 'senha' => 'password'])->json('token');

        $this->withToken($token)->deleteJson('/api/tokens/atual')->assertNoContent();

        // O guard guarda o usuário da requisição anterior; sem esquecer, o
        // teste passaria mesmo com o token ainda válido no banco.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/eu')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
