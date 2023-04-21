<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpsToUserSiteAccessStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_site_access_statistics', function (Blueprint $table) {
            $table->json('invalid_ips')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_site_access_statistics', function (Blueprint $table) {
            $table->dropColumn('invalid_ips');
        });
    }
}
