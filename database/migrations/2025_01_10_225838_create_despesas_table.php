<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('produto');
            $table->decimal('quantidade', 10, 2);
            $table->foreignId('fornecedor_id')->constrained('fornecedores');
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');
            $table->enum('status', ['PAGA', 'ABERTO'])->default('ABERTO');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('despesas');
    }
};