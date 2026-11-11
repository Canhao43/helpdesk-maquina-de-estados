<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Padrão é o papel com menos acesso: um usuário criado sem papel
            // explícito nunca ganha a fila inteira por engano.
            $table->string('papel', 20)->default('solicitante')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('papel');
        });
    }
};
