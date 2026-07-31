<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delve_logs', function (Blueprint $table) {
            $table->index(
                ['character_id', 'delve_exploration_id', 'created_at'],
                'delve_logs_character_exploration_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('delve_logs', function (Blueprint $table) {
            $table->dropIndex('delve_logs_character_exploration_created_index');
        });
    }
};
