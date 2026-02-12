<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            if (!Schema::hasColumn('registers', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('user_id');
                $table->foreign('city_id')->references('id')->on('cities')->restrictOnDelete();
                $table->index('city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            if (Schema::hasColumn('registers', 'city_id')) {
                $table->dropForeign(['city_id']);
                $table->dropIndex(['city_id']);
                $table->dropColumn('city_id');
            }
        });
    }
};
