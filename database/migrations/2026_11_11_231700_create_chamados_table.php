<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamados', function (Blueprint $table) {
            $table->id();
            // restrict: apagar um usuário não pode sumir com o histórico de
            // chamados dele. Usuário que sai é desativado, não apagado.
            $table->foreignId('solicitante_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('atendente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 120);
            $table->text('descricao');
            $table->string('prioridade', 10);
            $table->string('estado', 30)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamados');
    }
};
