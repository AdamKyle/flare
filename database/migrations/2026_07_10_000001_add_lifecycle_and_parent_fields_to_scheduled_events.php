<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_events', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_scheduled_event_id')->nullable()->after('raid_id');
            $table->string('status')->default('scheduled')->after('currently_running');
            $table->timestamp('cancelled_at')->nullable()->after('status');

            $table->foreign('parent_scheduled_event_id', 'scheduled_events_parent_fk')
                ->references('id')
                ->on('scheduled_events')
                ->nullOnDelete();

            $table->index(['parent_scheduled_event_id', 'status'], 'scheduled_events_parent_status_idx');

            $table->unique(['parent_scheduled_event_id', 'raid_id', 'start_date'], 'scheduled_events_parent_raid_start_unique');
        });

        DB::table('scheduled_events')->where('currently_running', true)->update(['status' => 'running']);

        DB::table('scheduled_events')
            ->where('currently_running', false)
            ->where('end_date', '<', now())
            ->update(['status' => 'completed']);

        DB::table('scheduled_events')
            ->where('currently_running', false)
            ->where('end_date', '>=', now())
            ->update(['status' => 'scheduled']);
    }

    public function down(): void
    {
        Schema::table('scheduled_events', function (Blueprint $table) {
            $table->dropUnique('scheduled_events_parent_raid_start_unique');
            $table->dropIndex('scheduled_events_parent_status_idx');
            $table->dropForeign('scheduled_events_parent_fk');
            $table->dropColumn(['parent_scheduled_event_id', 'status', 'cancelled_at']);
        });
    }
};
