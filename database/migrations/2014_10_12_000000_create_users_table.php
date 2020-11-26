<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('game_key')->unique()->nullable()->default(null);
            $table->string('private_game_key')->unique()->nullable()->default(null);
            $table->integer('message_throttle_count')->nullable()->default(0);
            $table->dateTime('can_speak_again_at')->nullable();
            $table->boolean('is_silenced')->nullable()->default(false);
            $table->string('ip_address')->nullable()->default('0.0.0.0');
            $table->boolean('is_banned')->nullable()->default(false);
            $table->dateTime('unbanned_at')->nullable();
            $table->string('banned_reason')->nullable();
            $table->string('un_ban_request')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
