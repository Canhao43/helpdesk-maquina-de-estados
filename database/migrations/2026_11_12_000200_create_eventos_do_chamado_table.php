<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_do_chamado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->restrictOnDelete();
            $table->foreignId('autor_id')->constrained('users')->restrictOnDelete();
            // Nulo na abertura do chamado; nos demais, a ação executada.
            $table->string('acao', 30)->nullable();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_novo', 30);
            $table->text('comentario')->nullable();
            $table->timestamp('created_at');

            $table->index(['chamado_id', 'id']);
        });

        // A trava de verdade fica no banco: o model também recusa alterar e
        // apagar, mas um UPDATE direto, um tinker ou outro sistema passariam
        // por cima dele. O projeto roda em SQLite; em MySQL seria um trigger
        // com SIGNAL SQLSTATE '45000'.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER eventos_do_chamado_sem_update BEFORE UPDATE ON eventos_do_chamado
            BEGIN SELECT RAISE(ABORT, 'O histórico do chamado não pode ser alterado.'); END;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER eventos_do_chamado_sem_delete BEFORE DELETE ON eventos_do_chamado
            BEGIN SELECT RAISE(ABORT, 'O histórico do chamado não pode ser apagado.'); END;
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_do_chamado');
    }
};
