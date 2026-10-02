<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiClientsTables extends Migration
{
    public function up()
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
            $table->string('client_id', 80)->unique();
            $table->text('secret_encrypted');
            $table->json('permissions')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_request_audits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('api_client_id');
            $table->string('request_id', 80)->unique();
            $table->string('user_reference')->nullable();
            $table->string('method', 10);
            $table->string('path', 500);
            $table->string('ip', 64)->nullable();
            $table->string('rut_hash', 64)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
            $table->index(['api_client_id', 'created_at']);
            $table->foreign('api_client_id')->references('id')->on('api_clients');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_request_audits');
        Schema::dropIfExists('api_clients');
    }
}
