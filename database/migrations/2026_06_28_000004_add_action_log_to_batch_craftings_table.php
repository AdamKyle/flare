<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_craftings', function (Blueprint $table) {
            $table->json('action_log')->nullable()->after('failed_count');
        });
    }

    public function down(): void
    {
        Schema::table('batch_craftings', function (Blueprint $table) {
            $table->dropColumn('action_log');
        });
    }
};
