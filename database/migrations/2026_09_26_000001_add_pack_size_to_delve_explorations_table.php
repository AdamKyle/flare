<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the configured pack size to Delve explorations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('delve_explorations', function (Blueprint $table): void {
            $table->unsignedInteger('pack_size')->default(1);
        });
    }

    /**
     * Remove the configured pack size from Delve explorations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('delve_explorations', function (Blueprint $table): void {
            $table->dropColumn('pack_size');
        });
    }
};
