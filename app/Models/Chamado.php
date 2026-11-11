<?php

namespace App\Models;

use App\Enums\EstadoChamado;
use App\Enums\Prioridade;
use Database\Factories\ChamadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// solicitante_id, atendente_id e estado ficam fora do fillable: quem abre é
// sempre o dono do token, e estado só muda por transição (M2).
#[Fillable(['titulo', 'descricao', 'prioridade'])]
class Chamado extends Model
{
    /** @use HasFactory<ChamadoFactory> */
    use HasFactory;

    protected $table = 'chamados';

    /**
     * O mesmo padrão do banco, para o chamado recém-criado já sair com a
     * versão certa sem precisar ser lido de novo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = ['versao' => 0];

    protected function casts(): array
    {
        return [
            'prioridade' => Prioridade::class,
            'estado' => EstadoChamado::class,
            'versao' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function atendente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendente_id');
    }

    /**
     * A regra de "quem vê o quê" na listagem mora aqui, na consulta. Filtrar
     * depois de buscar quebraria a paginação e o total contaria chamados que
     * o usuário não pode ver.
     *
     * @param  Builder<Chamado>  $query
     */
    #[Scope]
    protected function visiveisPara(Builder $query, User $usuario): void
    {
        if (! $usuario->papel->atende()) {
            $query->where('solicitante_id', $usuario->id);
        }
    }
}
