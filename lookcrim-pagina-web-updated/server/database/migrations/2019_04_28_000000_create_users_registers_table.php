<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_registers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nickname');
            $table->string('institution');
            $table->string('comment');
            $table->string('email')->unique();
            $table->string('token')->unique();
            $table->timestamp('created_at')->nullable();
            $table->tinyInteger('authorized')->default('0');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_registers');
    }
}
