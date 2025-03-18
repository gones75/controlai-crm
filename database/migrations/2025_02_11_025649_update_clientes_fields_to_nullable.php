<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateClientesFieldsToNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE clientes MODIFY email VARCHAR(255) NULL');
        DB::statement('ALTER TABLE clientes MODIFY telefone VARCHAR(255) NULL');
        DB::statement('ALTER TABLE clientes MODIFY cpf_cnpj VARCHAR(255) NULL');
        DB::statement('ALTER TABLE clientes MODIFY endereco VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE clientes MODIFY email VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE clientes MODIFY telefone VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE clientes MODIFY cpf_cnpj VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE clientes MODIFY endereco VARCHAR(255) NOT NULL');
    }
}
