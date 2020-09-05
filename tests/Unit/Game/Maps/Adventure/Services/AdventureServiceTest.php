<?php

namespace Tests\Unit\Game\Maps\Adventure\Services;

use App\Flare\Models\ItemAffix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Services\AdventureService;
use App\Game\Maps\Adventure\Builders\RewardBuilder;
use Tests\Setup\AdventureSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class AdventureServiceTest extends TestCase
{
    use RefreshDatabase, 
        CreateUser, 
        CreateAdventure, 
        CreateMonster,
        CreateItemAffix,
        CreateItem;

    public function setUp(): void {
        parent::setUp();

        Queue::fake();
        Event::fake();
    }

    public function testProcessAdventureCharacterLives()
    {
        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

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

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($adventure)
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


        $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

        $adventureService->processAdventure();
        $character->refresh();

        $this->assertFalse($character->is_dead);
        $this->assertTrue($character->adventureLogs->isNotEmpty());
        $this->assertTrue($character->adventureLogs->first()->complete);
        $this->assertTrue(!empty($character->adventureLogs->first()->rewards));
        $this->assertTrue(!empty($character->adventureLogs->first()->logs));
    }

    public function testProcessAdventureWithMultipleLevels()
    {
        $user = $this->createUser();

        $adventure = $this->createNewAdventure(null, 5);

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($adventure)
                                        ->setSkill('Accuracy', [
                                            'bonus' => 10,
                                            'xp_towards' => 10,
                                        ], true)
                                        ->setSkill('Dodge', [
                                            'bonus' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'bonus' => 10,
                                        ])
                                        ->getCharacter();


        $adventureService = new AdventureService($character, $adventure, new RewardBuilder, 'sample');

        $adventureService->processAdventure();
        $character->refresh();

        $this->assertEquals(5, $character->adventureLogs->first()->last_completed_level);

        foreach($character->adventureLogs->first()->logs as $key => $value) {
            $this->assertEquals(5, count($value));
        }
    }

    public function testProcessAdventureCharacterDies()
    {
        $user = $this->createUser();

        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 100,
            'dur' => 2,
            'dex' => 4,
            'chr' => 1,
            'int' => 1,
            'ac' => 100,
            'gold' => 1,
            'max_level' => 10,
            'health_range' => '999-9999',
            'attack_range' => '99-999',
            'drop_check' => 0.1,
        ]);

        $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->createAdventureLog($adventure)
                                        ->setSkill('Accuracy', [
                                            'accuracy_bonus' => 0,
                                        ])
                                        ->setSkill('Dodge', [
                                            'dodge_bonus' => 0,
                                        ])
                                        ->setSkill('Looting', [
                                            'looting_bonus' => 0,
                                        ])
                                        ->getCharacter();


        $adventureService = new AdventureService($character, $adventure, New RewardBuilder, 'sample');

        $adventureService->processAdventure();
        $character->refresh();

        $this->assertTrue($character->is_dead);
        $this->assertTrue($character->adventureLogs->isNotEmpty());
        $this->assertFalse($character->adventureLogs->first()->complete);
        $this->assertEquals(1, $character->adventureLogs->first()->last_completed_level);
    }

    public function testAppendNewLogs() {
        $user = $this->createUser();

        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 100,
            'dur' => 2,
            'dex' => 4,
            'chr' => 1,
            'int' => 1,
            'ac' => 1,
            'gold' => 1,
            'max_level' => 10,
            'health_range' => '999-9999',
            'attack_range' => '99-999',
            'drop_check' => 0.1,
        ]);

        $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($adventure, [
                                            'logs' => [['adventure' => 'sample']],
                                        ])
                                        ->setSkill('Accuracy', [
                                            'accuracy_bonus' => 10,
                                        ])
                                        ->setSkill('Dodge', [
                                            'dodge_bonus' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'looting_bonus' => 10,
                                        ])
                                        ->getCharacter();


        $adventureService = new AdventureService($character, $adventure, New RewardBuilder, 'sample');

        $adventureService->processAdventure();
        $character->refresh();

        $logs = $character->adventureLogs->first()->logs;
        
        // We start with one, for this test:
        $this->assertTrue(count($logs) > 1);
    }

    public function testStartAdventureWithExistingLevelCompletedAt() {
        $user = $this->createUser();

        $monster = $this->createMonster([
            'name' => 'Monster',
            'damage_stat' => 'str',
            'xp' => 10,
            'str' => 100,
            'dur' => 2,
            'dex' => 4,
            'chr' => 1,
            'int' => 1,
            'ac' => 1,
            'gold' => 1,
            'max_level' => 10,
            'health_range' => '999-9999',
            'attack_range' => '99-999',
            'drop_check' => 0.1,
        ]);

        $adventure = (new AdventureSetup)->setMonster($monster)->createAdventure();

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->levelCharacterUp(10)
                                        ->createAdventureLog($adventure, [
                                            'last_completed_level' => 1,
                                            'logs' => [
                                                'sample' => [
                                                [
                                                    "attacker" => "Sample",
                                                    "defender" => "Monster",
                                                    "messages" => [
                                                        0 => "Sample hit for 41"
                                                    ]
                                                    ],
                                                [
                                                    "attacker" => "Monster",
                                                    "defender" => "Sample",
                                                    "messages" => [
                                                        0 => "Monster hit for 250"
                                                    ]
                                                    ]
                                                ]
                                            ],
                                        ])
                                        ->setSkill('Accuracy', [
                                            'accuracy_bonus' => 10,
                                        ])
                                        ->setSkill('Dodge', [
                                            'dodge_bonus' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'looting_bonus' => 10,
                                        ])
                                        ->getCharacter();


        $adventureService = new AdventureService($character, $adventure, New RewardBuilder, 'another_name');

        $adventureService->processAdventure();
        $character->refresh();

        $logs = $character->adventureLogs->first()->logs;
        
        // We start with one, for this test:
        $this->assertTrue(!empty($logs));
        
        // There should be two logs in here for this adventure:
        $this->assertEquals(2, count($logs));
    }
}
