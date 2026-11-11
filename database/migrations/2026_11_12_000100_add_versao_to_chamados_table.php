<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            // Sobe a cada transição. A gravação só acontece se a versão no
            // banco ainda for a que foi lida: é o que impede dois atendentes
            // de assumirem o mesmo chamado ao mesmo tempo.
            $table->unsignedInteger('versao')->default(0)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropColumn('versao');
        });
    }
};
