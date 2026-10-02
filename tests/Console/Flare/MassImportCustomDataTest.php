<?php

namespace Tests\Console\Flare;

use App\Flare\GameImporter\Console\Commands\MassImportCustomData;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class MassImportCustomDataTest extends TestCase
{
    public function test_monsters_are_imported_after_quests_and_before_every_cache_rebuild(): void
    {
        config(['app.env' => 'production']);

        Artisan::shouldReceive('output')->andReturn('');
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'World Gems'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'Location Templates'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'Quests'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'Monsters'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('remove:racial-stat-bonuses', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('break:maps-into-pieces', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('create:gem-worlds', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('gem-rebuild:remove-alll-gems', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('create:character-attack-data', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('generate:monster-cache', [])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('create:quest-cache', [])->andReturn(0);

        $command = new MassImportCustomData;
        $command->setLaravel($this->app);
        $output = new BufferedOutput;

        $command->run(new ArrayInput([]), $output);

        $this->assertStringContainsString('COMPLETE: Import Monsters', $output->fetch());
    }

    public function test_a_failed_monster_import_stops_before_the_cache_rebuild_stages(): void
    {
        config(['app.env' => 'production']);

        Artisan::shouldReceive('output')->andReturn('');
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'World Gems'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'Location Templates'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'Quests'])->andReturn(0);
        Artisan::shouldReceive('call')->once()->ordered()->with('import:game-data', ['dirName' => 'Monsters'])->andReturn(1);
        Artisan::shouldReceive('call')->with('create:character-attack-data', [])->never();
        Artisan::shouldReceive('call')->with('generate:monster-cache', [])->never();
        Artisan::shouldReceive('call')->with('create:quest-cache', [])->never();

        $command = new MassImportCustomData;
        $command->setLaravel($this->app);
        $output = new BufferedOutput;

        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('FAILED: Import Monsters (exit code 1)', $output->fetch());
    }
}
