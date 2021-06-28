<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvasionDetailsToCharacters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->decimal('spell_evasion', 8, 4)->nullable()->default(0.0);
            $table->decimal('artifact_annulment', 8, 4)->nullable()->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('spell_evasion', 8, 4);
            $table->dropColumn('artifact_annulment', 8, 4);
        });
    }
}
