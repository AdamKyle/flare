<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIPAddressAndIsBannedToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->default('0.0.0.0');
            $table->boolean('is_banned')->nullable()->default(false);
            $table->dateTime('unbanned_at')->nullable();
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
            $table->dropColumn('ip_address');
            $table->dropColumn('is_banned');
            $table->dropColumn('unbanned_at');
        });
    }
}
