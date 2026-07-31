<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('scheduled_event_id')->nullable()->unique()->after('id');

            $table->foreign('scheduled_event_id', 'events_scheduled_event_fk')
                ->references('id')
                ->on('scheduled_events')
                ->nullOnDelete();
        });

        Schema::table('global_event_goals', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->nullable()->after('id');

            $table->index('event_id', 'global_event_goals_event_id_idx');

            $table->foreign('event_id', 'global_event_goals_event_fk')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();
        });

        $this->backfillEventScheduledEventId();
        $this->backfillGlobalEventGoalEventId();
    }

    private function backfillEventScheduledEventId(): void
    {
        $events = DB::table('events')->whereNull('scheduled_event_id')->get();

        foreach ($events as $event) {
            $candidates = DB::table('scheduled_events')
                ->where('event_type', $event->type)
                ->when(! is_null($event->raid_id), fn ($query) => $query->where('raid_id', $event->raid_id))
                ->when(is_null($event->raid_id), fn ($query) => $query->whereNull('raid_id'))
                ->where('end_date', $event->ends_at)
                ->pluck('id');

            if ($candidates->count() !== 1) {
                continue;
            }

            $scheduledEventId = $candidates->first();

            $alreadyLinked = DB::table('events')->where('scheduled_event_id', $scheduledEventId)->exists();

            if ($alreadyLinked) {
                continue;
            }

            DB::table('events')->where('id', $event->id)->update(['scheduled_event_id' => $scheduledEventId]);
        }
    }

    private function backfillGlobalEventGoalEventId(): void
    {
        $goals = DB::table('global_event_goals')->whereNull('event_id')->get();

        foreach ($goals as $goal) {
            $candidates = DB::table('events')->where('type', $goal->event_type)->pluck('id');

            if ($candidates->count() !== 1) {
                continue;
            }

            DB::table('global_event_goals')->where('id', $goal->id)->update(['event_id' => $candidates->first()]);
        }
    }

    public function down(): void
    {
        Schema::table('global_event_goals', function (Blueprint $table) {
            $table->dropForeign('global_event_goals_event_fk');
            $table->dropIndex('global_event_goals_event_id_idx');
            $table->dropColumn('event_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign('events_scheduled_event_fk');
            $table->dropColumn('scheduled_event_id');
        });
    }
};
