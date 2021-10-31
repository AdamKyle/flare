<?php

namespace Tests\Unit\Flare\Traits\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Traits\Controllers\MonstersShowInformation;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class MonstersShowInformationTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateMonster;

    public function setUp(): void {
        parent::setUp();
    }

    public function testGetMonsterShowWithQuestItem() {
        $item = $this->createItem(['type' => 'quest']);
        $monster = $this->createMonster([
            'quest_item_id' => $item->id,
        ]);

        $trait = $this->getObjectForTrait(MonstersShowInformation::class);

        $monsterView = $trait->renderMonsterShow($monster);

        $this->assertInstanceOf(View::class, $monsterView);
    }
}
