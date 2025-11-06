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
            $table->longText('instructions')->nullable()->change();
            $table->integer('xp_reward')->nullable()->change();

            $table->dropColumn(['intro_text', 'desktop_instructions', 'mobile_instructions']);

            $table->json('intro_text')->nullable()->after('name');
            $table->json('desktop_instructions')->nullable()->after('instructions');
            $table->json('mobile_instructions')->nullable()->after('desktop_instructions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
