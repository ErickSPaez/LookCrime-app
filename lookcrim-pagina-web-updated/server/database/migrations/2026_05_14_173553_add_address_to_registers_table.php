<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            if (!Schema::hasColumn('registers', 'address')) {
                $table->text('address')->nullable()->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('registers', function (Blueprint $table) {
            if (Schema::hasColumn('registers', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};