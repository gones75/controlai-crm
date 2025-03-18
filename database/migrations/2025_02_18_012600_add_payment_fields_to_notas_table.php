<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPaymentFieldsToNotasTable extends Migration
{
    public function up()
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->decimal('valor_pago', 10, 2)->default(0)->after('valor_total');
            $table->decimal('valor_pendente', 10, 2)->default(0)->after('valor_pago');
        });

        // Atualizar valores para notas existentes
        DB::table('notas')->where('status', 'ABERTA')
            ->update(['valor_pendente' => DB::raw('valor_total')]);
            
        DB::table('notas')->where('status', 'PAGA')
            ->update(['valor_pago' => DB::raw('valor_total')]);
    }

    public function down()
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn(['valor_pago', 'valor_pendente']);
        });
    }
}