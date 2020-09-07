<?php

namespace Tests\Unit\Game\Maps\Adventure\Services;

use App\Flare\Models\ItemAffix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Services\AdventureFightService;
use App\Game\Maps\Adventure\Services\AdventureService;
use Tests\Setup\AdventureSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class AdventureFightServiceTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateAdventure,
        CreateMonster,
        CreateItemAffix,
        CreateItem;

    private $adventure    = null;

    private $character    = null;

    private $fightService = null;

    public function setUp(): void {
        parent::setUp();

        $this->setUpBaseEnviroment();

        $this->fightService = new AdventureFightService($this->character, $this->adventure);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->adventure    = null;

        $this->character    = null;

        $this->fightService = null;
    }

    public function testProcessBattle() {
        $this->fightService->processBattle();

        $logs = $this->fightService->getLogInformation();

        $this->assertFalse(empty($logs));
    }

    public function testCantHit() {

        $fightService = \Mockery::mock(AdventureFightService::class, [
            $this->character, $this->adventure
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $fightService->shouldReceive('canHit')->andReturn(false);

        $fightService->processBattle();

        $logs = $fightService->getLogInformation();

        $this->assertFalse(empty($logs));
        $this->assertEquals($logs[0]['message'], $this->character->name . ' Missed!');
    }

    public function testCantBlock() {

        $fightService = \Mockery::mock(AdventureFightService::class, [
            $this->character, $this->adventure
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $fightService->shouldReceive('blockedAttack')->andReturn(true);

        $fightService->processBattle();

        $logs = $fightService->getLogInformation();

        $this->assertFalse(empty($logs));
        $this->assertEquals($logs[0]['message'], 'Monster blocked the attack!');
    }

    protected function setUpBaseEnviroment() {
        $user = $this->createUser();

        $this->adventure = $this->createNewAdventure();

        $this->createItemAffix([
            'name'                 => 'Healing',
            'base_damage_mod'      => '0.01',
            'type'                 => 'prefix',
            'description'          => 'healing',
            'base_healing_mod'     => '0.20',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'ac_mod'               => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $this->createItemAffix([
            'name'                 => 'Dex Boost',
            'base_damage_mod'      => '0.05',
            'type'                 => 'suffix',
            'description'          => 'dex boost',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.50',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'ac_mod'               => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $healingSpell = $this->createItem([
            'name'           => 'Healing',
            'type'           => 'spell-healing',
            'base_damage'    => 0,
            'base_healing'   => 10,
            'cost'           => 10,
            'item_prefix_id' => ItemAffix::where('name', 'Healing')->first()->id,
        ]);

        $artifact = $this->createItem([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $damageSpell = $this->createItem([
            'name'           => 'Damage',
            'type'           => 'spell-damage',
            'base_damage'    => 0,
            'cost'           => 10,
            'item_suffix_id' => ItemAffix::where('name', 'Dex Boost')->first()->id,
        ]);

        $this->character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($this->adventure)
                                        ->giveItem($healingSpell)
                                        ->giveItem($damageSpell)
                                        ->giveItem($artifact)
                                        ->equipSpellSlot(1, 'spell-one')
                                        ->equipSpellSlot(2, 'spell-two')
                                        ->equipArtifact(3)
                                        ->setSkill('Accuracy', [
                                            'bonus' => 10,
                                            'xp_towards' => 10,
                                        ], true)
                                        ->setSkill('Dodge', [
                                            'bonus' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'bonus' => 0,
                                        ])
                                        ->getCharacter();
    }
}