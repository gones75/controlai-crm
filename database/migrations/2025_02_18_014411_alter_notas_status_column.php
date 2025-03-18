<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterNotasStatusColumn extends Migration
{
    public function up()
    {
        Schema::table('notas', function (Blueprint $table) {
            
            $table->string('status_new')->default('ABERTA')->after('status');
        });

        DB::statement('UPDATE notas SET status_new = status');

        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('notas', function (Blueprint $table) {
            $table->string('status_old')->default('ABERTA')->after('status');
        });

        DB::statement('UPDATE notas SET status_old = status');

        Schema::table('notas', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('notas', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });
    }
}