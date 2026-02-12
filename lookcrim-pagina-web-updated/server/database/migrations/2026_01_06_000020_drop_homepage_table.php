<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('homepage');
    }

    public function down(): void
    {
        Schema::create('homepage', function (Blueprint $table) {
            $table->id();
            $table->longText('center_text_en');
            $table->longText('center_text_pt');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('image')->default('');
        });
    }
};
