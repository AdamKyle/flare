<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the legacy Fire/Water/Ice character Gem atonement columns replaced by character Gem modifiers.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('gems', function (Blueprint $table): void {
            $table->dropColumn([
                'primary_atonement_type',
                'secondary_atonement_type',
                'tertiary_atonement_type',
                'primary_atonement_amount',
                'secondary_atonement_amount',
                'tertiary_atonement_amount',
            ]);
        });
    }

    /**
     * Restore the legacy character Gem atonement columns as nullable columns.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('gems', function (Blueprint $table): void {
            $table->integer('primary_atonement_type')->nullable();
            $table->integer('secondary_atonement_type')->nullable();
            $table->integer('tertiary_atonement_type')->nullable();
            $table->decimal('primary_atonement_amount', 12, 8)->nullable();
            $table->decimal('secondary_atonement_amount', 12, 8)->nullable();
            $table->decimal('tertiary_atonement_amount', 12, 8)->nullable();
        });
    }
};
