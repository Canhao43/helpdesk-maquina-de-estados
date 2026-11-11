<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChamadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_abre_chamado_e_responde_201_com_location(): void
    {
        $solicitante = User::factory()->create();
        Sanctum::actingAs($solicitante, ['*']);

        $resposta = $this->postJson('/api/chamados', [
            'titulo' => 'Impressora do 3º andar não imprime',
            'descricao' => 'Desde ontem a fila trava no primeiro documento.',
            'prioridade' => 'alta',
        ]);

        $resposta->assertCreated()
            ->assertHeader('Location', route('chamados.show', $resposta->json('data.id')))
            ->assertJsonPath('data.estado', 'aberto')
            ->assertJsonPath('data.prioridade', 'alta')
            ->assertJsonPath('data.solicitante.id', $solicitante->id)
            ->assertJsonPath('data.atendente', null);
    }

    public function test_o_dono_do_chamado_e_sempre_quem_esta_logado(): void
    {
        $solicitante = User::factory()->create();
        $outro = User::factory()->create();
        Sanctum::actingAs($solicitante, ['*']);

        $resposta = $this->postJson('/api/chamados', [
            'titulo' => 'Sem acesso ao e-mail',
            'descricao' => 'Senha expirada.',
            'solicitante_id' => $outro->id,
            'atendente_id' => $outro->id,
            'estado' => 'resolvido',
        ]);

        $resposta->assertCreated()
            ->assertJsonPath('data.solicitante.id', $solicitante->id)
            ->assertJsonPath('data.atendente', null)
            ->assertJsonPath('data.estado', 'aberto');
    }

    public function test_prioridade_omitida_vira_media(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->postJson('/api/chamados', ['titulo' => 'Mouse quebrado', 'descricao' => 'Botão esquerdo falha.'])
            ->assertCreated()
            ->assertJsonPath('data.prioridade', 'media');
    }

    public function test_valida_os_campos(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->postJson('/api/chamados', ['descricao' => str_repeat('a', 5001), 'prioridade' => 'urgentissima'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titulo', 'descricao', 'prioridade']);

        $this->assertDatabaseCount('chamados', 0);
    }

    public function test_sem_token_nao_abre_nem_lista(): void
    {
        $this->postJson('/api/chamados', ['titulo' => 'x', 'descricao' => 'y'])->assertUnauthorized();
        $this->getJson('/api/chamados')->assertUnauthorized();
    }

    public function test_solicitante_lista_so_os_proprios(): void
    {
        $solicitante = User::factory()->create();
        $meus = Chamado::factory()->count(2)->for($solicitante, 'solicitante')->create();
        Chamado::factory()->count(3)->create();
        Sanctum::actingAs($solicitante, ['*']);

        $resposta = $this->getJson('/api/chamados')->assertOk();

        $this->assertEqualsCanonicalizing($meus->pluck('id')->all(), collect($resposta->json('data'))->pluck('id')->all());
        // O total da paginação também não pode contar o que ele não vê.
        $resposta->assertJsonPath('meta.total', 2);
    }

    public function test_atendente_e_admin_listam_todos(): void
    {
        Chamado::factory()->count(3)->create();

        foreach ([User::factory()->atendente()->create(), User::factory()->admin()->create()] as $usuario) {
            Sanctum::actingAs($usuario, ['*']);
            $this->getJson('/api/chamados')->assertOk()->assertJsonPath('meta.total', 3);
        }
    }

    public function test_solicitante_ve_o_proprio_chamado(): void
    {
        $chamado = Chamado::factory()->create();
        Sanctum::actingAs($chamado->solicitante, ['*']);

        $this->getJson("/api/chamados/{$chamado->id}")->assertOk()->assertJsonPath('data.id', $chamado->id);
    }

    public function test_chamado_de_outra_pessoa_responde_igual_a_inexistente(): void
    {
        $alheio = Chamado::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $deOutro = $this->getJson("/api/chamados/{$alheio->id}");
        $inexistente = $this->getJson('/api/chamados/999999');

        $deOutro->assertNotFound();
        $inexistente->assertNotFound();
        $this->assertSame($inexistente->json(), $deOutro->json());
    }

    public function test_atendente_ve_chamado_de_qualquer_pessoa(): void
    {
        $chamado = Chamado::factory()->create();
        Sanctum::actingAs(User::factory()->atendente()->create(), ['*']);

        $this->getJson("/api/chamados/{$chamado->id}")->assertOk();
    }
}
