<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the Gem World Scroll fields to Items. Also adds the `randomly_generated`
     * flag itself, which `Item` already casts as boolean but which was never actually
     * added to the `items` table (only `item_affixes` has it); the Gem Scroll generator
     * requires this real column instead of a second generated-item flag.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->boolean('randomly_generated')->default(false)->after('is_cosmic');
            $table->string('gem_scroll_type')->nullable()->after('randomly_generated');
            $table->decimal('gem_scroll_bonus', 8, 4)->nullable()->after('gem_scroll_type');
            $table->string('gem_scroll_currency_type')->nullable()->after('gem_scroll_bonus');
            $table->decimal('gem_scroll_socket_chance', 8, 4)->nullable()->after('gem_scroll_currency_type');
            $table->decimal('gem_scroll_pre_gem_chance', 8, 4)->nullable()->after('gem_scroll_socket_chance');

            $table->index('gem_scroll_type');
            $table->index('gem_scroll_currency_type');
        });
    }

    /**
     * Remove the Gem World Scroll fields and the `randomly_generated` flag from Items.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropIndex(['gem_scroll_type']);
            $table->dropIndex(['gem_scroll_currency_type']);

            $table->dropColumn([
                'randomly_generated',
                'gem_scroll_type',
                'gem_scroll_bonus',
                'gem_scroll_currency_type',
                'gem_scroll_socket_chance',
                'gem_scroll_pre_gem_chance',
            ]);
        });
    }
};
