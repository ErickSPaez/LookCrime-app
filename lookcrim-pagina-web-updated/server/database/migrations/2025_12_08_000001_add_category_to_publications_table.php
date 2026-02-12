<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->string('category')->nullable()->after('private');
        });
    }

    public function down()
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
