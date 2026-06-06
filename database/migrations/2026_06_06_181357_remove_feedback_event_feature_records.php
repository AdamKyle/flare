<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $eventIds = DB::table('events')->where('type', 9)->pluck('id');

        if ($eventIds->isNotEmpty()) {
            DB::table('announcements')->whereIn('event_id', $eventIds)->delete();
        }

        DB::table('events')->where('type', 9)->delete();

        DB::table('scheduled_events')->where('event_type', 9)->delete();

        $eventsPage = DB::table('info_pages')->where('page_name', 'events')->first();

        if (! is_null($eventsPage)) {
            $sections = json_decode($eventsPage->page_sections, true);

            if (is_array($sections)) {
                $filtered = array_values(array_filter($sections, function ($section) {
                    $content = $section['content'] ?? '';

                    return stripos($content, 'Feedback Events') === false && stripos($content, 'feedback event') === false;
                }));

                DB::table('info_pages')->where('page_name', 'events')->update([
                    'page_sections' => json_encode($filtered),
                ]);
            }
        }
    }

    public function down(): void {}
};
