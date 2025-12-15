<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists('newsletter_section');
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('newsletter');
        Schema::dropIfExists('news');
        Schema::dropIfExists('project');
        Schema::dropIfExists('team');
        Schema::dropIfExists('researches');
        Schema::dropIfExists('contact');
    }

    public function down()
    {
    }
};
