<?php

namespace Tests\Unit\Admin\Import\ClassSpecials\Sheets;

use App\Admin\Import\ClassSpecials\Sheets\ClassSpecialsSheet;
use App\Flare\Models\GameClassSpecial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;

class ClassSpecialsSheetTest extends TestCase
{
    use CreateClass, CreateGameClassSpecial, RefreshDatabase;

    public function testCollectionCreatesNewClassSpecialFromRow(): void
    {
        $gameClass = $this->createClass(['name' => 'Beastmaster', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        (new ClassSpecialsSheet)->collection(new Collection([
            new Collection(['name', 'description', 'base_damage_mod', 'game_class_id', 'requires_class_rank_level']),
            new Collection(["Devil's Piercing Shot", 'Double bow damage plus bleeds.', 0.15, $gameClass->id, 0]),
        ]));

        $this->assertSame(1, GameClassSpecial::count());
        $this->assertSame("Devil's Piercing Shot", GameClassSpecial::first()->name);
    }

    public function testCollectionUpdatesExistingClassSpecialByName(): void
    {
        $gameClass = $this->createClass(['name' => 'Beastmaster', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $this->createGameClassSpecial([
            'game_class_id' => $gameClass->id,
            'name' => 'Beast Stomp',
            'description' => 'Old description.',
            'base_damage_mod' => 0.10,
        ]);

        (new ClassSpecialsSheet)->collection(new Collection([
            new Collection(['name', 'description', 'base_damage_mod', 'game_class_id', 'requires_class_rank_level']),
            new Collection(['Beast Stomp', 'Double hammer damage plus earth crust.', 0.20, $gameClass->id, 0]),
        ]));

        $this->assertSame(1, GameClassSpecial::count());
        $this->assertSame('Double hammer damage plus earth crust.', GameClassSpecial::first()->description);
    }

    public function testCollectionSkipsHeaderRow(): void
    {
        (new ClassSpecialsSheet)->collection(new Collection([
            new Collection(['name', 'description', 'base_damage_mod']),
        ]));

        $this->assertSame(0, GameClassSpecial::count());
    }
}
