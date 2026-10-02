<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContactFieldsToPersonaTable extends Migration
{
    protected $connection = 'personas_fast';

    public function up()
    {
        Schema::connection($this->connection)->table('persona', function (Blueprint $table) {
            $table->string('email')->nullable()->after('apmaterno');
            $table->string('telefono', 50)->nullable()->after('email');
            $table->text('direccion_encrypted')->nullable()->after('telefono');
        });
    }

    public function down()
    {
        Schema::connection($this->connection)->table('persona', function (Blueprint $table) {
            $table->dropColumn(['email', 'telefono', 'direccion_encrypted']);
        });
    }
}
