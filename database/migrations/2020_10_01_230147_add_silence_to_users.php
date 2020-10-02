<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSilenceToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('message_throttle_count')->nullable()->default(0);
            $table->dateTime('can_speak_again_at')->nullable();
            $table->boolean('is_silenced')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('message_throttle_count');
            $table->dropColumn('can_speak_again_at');
            $table->dropColumn('is_silenced');
        });
    }
}
