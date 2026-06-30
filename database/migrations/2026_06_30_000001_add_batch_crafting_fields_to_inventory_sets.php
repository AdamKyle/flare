<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_sets', function (Blueprint $table) {
            $table->string('special_type')->nullable()->index('inventory_sets_special_type_index');
            $table->unsignedInteger('max_slots')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_sets', function (Blueprint $table) {
            $table->dropIndex('inventory_sets_special_type_index');
            $table->dropColumn(['special_type', 'max_slots']);
        });
    }
};
