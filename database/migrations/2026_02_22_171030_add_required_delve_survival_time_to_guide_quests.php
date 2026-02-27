<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->bigInteger('required_delve_survival_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guide_quests', function (Blueprint $table) {
            $table->dropColumn('required_delve_survival_time');
        });
    }
};
