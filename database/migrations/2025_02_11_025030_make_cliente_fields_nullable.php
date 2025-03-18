<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeClienteFieldsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('telefone')->nullable()->change();
            $table->string('cpf_cnpj')->nullable()->change();
            $table->string('endereco')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('telefone')->nullable(false)->change();
            $table->string('cpf_cnpj')->nullable(false)->change();
            $table->string('endereco')->nullable(false)->change();
        });
    }
}
