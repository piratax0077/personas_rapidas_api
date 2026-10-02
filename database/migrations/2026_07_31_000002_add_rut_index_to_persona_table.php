<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRutIndexToPersonaTable extends Migration
{
    public function up()
    {
        $index = DB::select("SHOW INDEX FROM `persona` WHERE Key_name = 'persona_rut_index'");

        if (empty($index)) {
            Schema::table('persona', function (Blueprint $table) {
                $table->index('rut', 'persona_rut_index');
            });
        }
    }

    public function down()
    {
        $index = DB::select("SHOW INDEX FROM `persona` WHERE Key_name = 'persona_rut_index'");

        if (! empty($index)) {
            Schema::table('persona', function (Blueprint $table) {
                $table->dropIndex('persona_rut_index');
            });
        }
    }
}
