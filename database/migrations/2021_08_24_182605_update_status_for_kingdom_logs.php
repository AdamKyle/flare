<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusForKingdomLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE kingdom_logs Modify COLUMN status ENUM('attacked kingdom','lost attack','taken kingdom','lost kingdom','kingdom attacked','units returning', 'bombs dropped') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE kingdom_logs Modify COLUMN status ENUM('attacked kingdom','lost attack','taken kingdom','lost kingdom','kingdom attacked','units returning') NOT NULL");
    }
}
