<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_showing_survey')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_showing_survey');
            });
        }

        Schema::dropIfExists('submitted_surveys');
        Schema::dropIfExists('survey_snapshots');
        Schema::dropIfExists('surveys');
    }

    public function down(): void
    {
    }
};
