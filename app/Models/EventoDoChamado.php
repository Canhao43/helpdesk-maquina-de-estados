<?php

namespace App\Models;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Uma linha por coisa que aconteceu com o chamado. Só se acrescenta: o
 * model recusa alterar e apagar, e o banco também (triggers na migration).
 */
class EventoDoChamado extends Model
{
    protected $table = 'eventos_do_chamado';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'acao' => Acao::class,
            'estado_anterior' => EstadoChamado::class,
            'estado_novo' => EstadoChamado::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('O histórico do chamado não pode ser alterado.'));
        static::deleting(fn () => throw new LogicException('O histórico do chamado não pode ser apagado.'));
    }

    /**
     * @return BelongsTo<Chamado, $this>
     */
    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
