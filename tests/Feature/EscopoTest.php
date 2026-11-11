<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscopoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Token de verdade, pelo login: o escopo que importa é o que fica gravado.
     *
     * @param  list<string>|null  $escopos
     */
    private function token(User $usuario, ?array $escopos = null): string
    {
        $corpo = ['email' => $usuario->email, 'senha' => 'password'] + ($escopos ? ['escopos' => $escopos] : []);

        return $this->postJson('/api/tokens', $corpo)->assertCreated()->json('token');
    }

    /**
     * Cada requisição como se fosse nova: o guard guarda o usuário da anterior.
     */
    private function com(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    public function test_sem_escopos_o_token_pode_tudo_o_que_o_papel_deixa(): void
    {
        $usuario = User::factory()->create();
        $token = $this->token($usuario);

        $this->com($token)->getJson('/api/eu')->assertOk()->assertJsonPath('escopos', ['*']);
        $this->com($token)->postJson('/api/chamados', ['titulo' => 'x', 'descricao' => 'y'])->assertCreated();
        $this->com($token)->getJson('/api/chamados')->assertOk();
    }

    public function test_token_que_so_abre_nao_le_nem_age(): void
    {
        $usuario = User::factory()->admin()->create();
        $token = $this->token($usuario, ['chamados:abrir']);
        $chamado = Chamado::factory()->create();

        $this->com($token)->getJson('/api/eu')->assertOk()->assertJsonPath('escopos', ['chamados:abrir']);
        $this->com($token)->postJson('/api/chamados', ['titulo' => 'Alerta do monitor', 'descricao' => 'Disco a 95%.'])->assertCreated();
        $this->com($token)->getJson('/api/chamados')->assertForbidden();
        $this->com($token)->getJson("/api/chamados/{$chamado->id}")->assertForbidden();
        $this->com($token)->getJson("/api/chamados/{$chamado->id}/historico")->assertForbidden();
        // Mesmo sendo de um admin: o escopo é teto.
        $this->com($token)->postJson("/api/chamados/{$chamado->id}/acoes/assumir")->assertForbidden();
        $this->assertNull($chamado->fresh()->atendente_id);
    }

    public function test_token_que_so_le_nao_abre_nem_age(): void
    {
        $atendente = User::factory()->atendente()->create();
        $token = $this->token($atendente, ['chamados:ler']);
        $chamado = Chamado::factory()->create();

        $this->com($token)->getJson("/api/chamados/{$chamado->id}")->assertOk();
        $this->com($token)->postJson('/api/chamados', ['titulo' => 'x', 'descricao' => 'y'])->assertForbidden();
        $this->com($token)->postJson("/api/chamados/{$chamado->id}/acoes/assumir")->assertForbidden();
    }

    public function test_escopo_nao_da_ao_token_mais_do_que_o_papel(): void
    {
        $solicitante = User::factory()->create();
        $token = $this->token($solicitante, ['chamados:ler', 'chamados:agir']);
        $chamado = Chamado::factory()->for($solicitante, 'solicitante')->create();

        $this->com($token)->postJson("/api/chamados/{$chamado->id}/acoes/assumir")->assertForbidden();
    }

    public function test_sem_leitura_existente_e_inexistente_respondem_igual(): void
    {
        $token = $this->token(User::factory()->create(), ['chamados:abrir']);
        $chamado = Chamado::factory()->create();
        // Como em produção: com a depuração ligada, o corpo traz o trace, e a
        // linha do teste que fez a chamada já basta para os dois diferirem.
        config(['app.debug' => false]);

        $existente = $this->com($token)->getJson("/api/chamados/{$chamado->id}");
        $inexistente = $this->com($token)->getJson('/api/chamados/999999');

        // Se a busca pelo id viesse antes do escopo, o inexistente daria 404.
        $existente->assertForbidden();
        $inexistente->assertForbidden();
        $this->assertSame($existente->json(), $inexistente->json());
    }

    public function test_escopo_desconhecido_e_recusado(): void
    {
        $usuario = User::factory()->create();

        $this->postJson('/api/tokens', ['email' => $usuario->email, 'senha' => 'password', 'escopos' => ['chamados:apagar']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('escopos.0');
    }
}
