<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DelveLogsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_delve_logs_has_composite_index_for_latest_pack_size_lookup(): void
    {
        $indexRows = collect(DB::select("show index from delve_logs where Key_name = 'delve_logs_character_exploration_created_index'"));
        $columns = $indexRows->sortBy('Seq_in_index')->pluck('Column_name')->values()->all();

        $this->assertSame(['character_id', 'delve_exploration_id', 'created_at'], $columns);
    }
}
