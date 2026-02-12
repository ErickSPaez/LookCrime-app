<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameAndEditNewsletterUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('newsletter', function (Blueprint $table) {
            $table->rememberToken();
            $table->tinyInteger('confirmed')->default('0');
            $table->dropColumn('email_verified_at');

        });

        Schema::rename('newsletter', 'newsletter_subscribers');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
}
