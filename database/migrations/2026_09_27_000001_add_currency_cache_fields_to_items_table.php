<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the Compensation Cache fields to Items.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->unsignedBigInteger('cache_amount')->nullable()->after('gem_scroll_pre_gem_chance');
            $table->string('currency_cache_type')->nullable()->after('cache_amount');
            $table->index('currency_cache_type');
        });
    }

    /**
     * Remove the Compensation Cache fields from Items.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropIndex(['currency_cache_type']);
            $table->dropColumn('cache_amount');
            $table->dropColumn('currency_cache_type');
        });
    }
};
