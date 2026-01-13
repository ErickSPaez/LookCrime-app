<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('register_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_id')->constrained('registers')->cascadeOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['register_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('register_images');
    }
};
