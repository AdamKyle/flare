<?php

namespace Tests\Feature\Admin\Quests;

use App\Flare\Models\NpcCommand;
use App\Flare\Values\NpcCommandTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

class QuestsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateQuest,
        CreateNpc,
        CreateItem,
        CreateGameMap;

    private $user;

    private $quest;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->createGameMap();

        $npcId = $this->createNpc()->id;

        NpcCommand::create([
            'npc_id' => $npcId,
            'command' => 'Sample',
            'command_type' => NpcCommandTypes::QUEST,
        ]);

        $this->quest = $this->createQuest([
            'name'             => 'sample',
            'npc_id'           => $npcId,
            'item_id'          => $this->createItem()->id,
            'gold_dust_cost'   => 10000,
            'shard_cost'       => 10000,
            'gold_cost'        => 10000,
            'reward_item'      => $this->createItem()->id,
            'reward_gold_dust' => 10000,
            'reward_shards'    => 10000,
            'reward_gold'      => 10000,
            'reward_xp'        => 100,
            'unlocks_skill'    => false,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testAdminCanSeeQuestsPage()
    {
        $this->actingAs($this->user)->visit(route('quests.index'))->see('Quests');
    }

    public function testNonAdminCannotSeeQuestsPage()
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->actingAs($user)->visit(route('game'))->visit(route('quests.index'))->see('You don\'t have permission to view that.');
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('quests.create'))->see('Create Quest');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('quests.show', [
            'quest' => $this->quest
        ]))->see($this->quest->name);
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('quests.edit', [
            'quest' => $this->quest
        ]))->see($this->quest->name);
    }

    public function testCanSeeExportPage() {
        $this->actingAs($this->user)->visit(route('quests.export'))->see('Export');
    }

    public function testCanSeeMonsterImportPage() {
        $this->actingAs($this->user)->visit(route('quests.import'))->see('Import Quest Data');
    }
}
