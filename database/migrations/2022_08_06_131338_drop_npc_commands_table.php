<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration  {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::dropIfExists('npc_commands');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Do not bring it back.
    }
};
