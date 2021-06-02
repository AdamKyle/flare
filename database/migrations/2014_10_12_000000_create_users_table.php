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
            $table->integer('message_throttle_count')->nullable()->default(0);
            $table->dateTime('can_speak_again_at')->nullable();
            $table->boolean('is_silenced')->default(false);
            $table->string('ip_address')->nullable()->default('0.0.0.0');
            $table->boolean('is_banned')->default(false);
            $table->dateTime('unbanned_at')->nullable();
            $table->dateTime('timeout_until')->nullable();
            $table->string('banned_reason')->nullable();
            $table->string('un_ban_request')->nullable();
            $table->boolean('adventure_email')->default(true);
            $table->boolean('new_building_email')->default(true);
            $table->boolean('upgraded_building_email')->default(false);
            $table->boolean('kingdoms_update_email')->default(false);
            $table->boolean('rebuilt_building_email')->default(false);
            $table->boolean('kingdom_attack_email')->default(false);
            $table->boolean('unit_recruitment_email')->default(false);
            $table->boolean('is_test')->default(false);
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
