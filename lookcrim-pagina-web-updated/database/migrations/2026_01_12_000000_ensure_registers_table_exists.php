<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registers')) {
            return;
        }

        // Legacy installs used `publications`. New code uses `registers`.
        if (Schema::hasTable('publications')) {
            Schema::rename('publications', 'registers');
            return;
        }

        // Fallback for completely fresh DBs without the legacy migration.
        Schema::create('registers', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_pt');
            $table->longText('content_en');
            $table->longText('content_pt');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('image')->default('');
            $table->string('embed_url')->nullable();
            $table->string('embed_url_en')->nullable();
            $table->tinyInteger('private')->default('0');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('publications')) {
            return;
        }

        if (Schema::hasTable('registers')) {
            Schema::rename('registers', 'publications');
        }
    }
};
