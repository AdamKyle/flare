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
        Schema::table('kingdom_logs', function (Blueprint $table) {
            $table->json('old_buildings')->nullable()->change();
            $table->json('new_buildings')->nullable()->change();
            $table->json('old_units')->nullable()->change();
            $table->json('new_units')->nullable()->change();
            $table->decimal('morale_loss', 5, 4)->nullable()->change();
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
