<?php

namespace Tests\Unit\Game\Adventure\Services;

use App\Flare\Values\ItemEffectsValue;
use App\Game\Adventures\View\AdventureCompletedRewards;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class AdventureCompletedRewardsTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateGameSkill;

    private $rewards;

    private $item;

    private $questItem;

    private $skill;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = $this->createItem();

        $this->questItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::SHADOWPLANE
        ]);

        $this->skill = $this->createGameSkill([
            'name' => 'Astral Magics'
        ]);

        $this->rewards = [
            'Level 1' =>
                [
                    'Goblin-N0Km0lwpDy' =>
                        [
                            'exp' => 3,
                            'gold' => 25,
                            'items' =>
                                [
                                    0 =>
                                        [
                                            'id' => $this->item->id,
                                            'name' => $this->item->affix_name,
                                        ],
                                ],
                            'skill' =>
                                [
                                    'exp' => 20,
                                    'skill_name' => 'Astral Magics',
                                    'exp_towards' => 0.1,
                                ],
                        ],
                    'Dead Priest-NIy5tZ00Bl' =>
                        [
                            'exp' => 3,
                            'gold' => 50,
                            'items' =>
                                [
                                    0 =>
                                        [
                                            'id' => $this->questItem->id,
                                            'name' => $this->questItem->affix_name,
                                        ],
                                ],
                            'skill' =>
                                [
                                    'exp' => 20,
                                    'skill_name' => 'Astral Magics',
                                    'exp_towards' => 0.1,
                                ],
                        ],
                ],
        ];
    }

    public function testBaselineProcessRewards()
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->assignSkill($this->skill)->trainSkill('Astral Magics');

        $rewards = AdventureCompletedRewards::CombineRewards($this->rewards, $character->getCharacter(false));

        $this->assertGreaterThan(0, $rewards['exp']);
        $this->assertGreaterThan(0, $rewards['gold']);
        $this->assertGreaterThan(0, $rewards['skill']['exp']);
        $this->assertEquals(0.1, $rewards['skill']['exp_towards']);
        $this->assertEquals('Astral Magics', $rewards['skill']['skill_name']);
        $this->assertNotEmpty($rewards['items']);

    }

    public function testBaselineProcessRewardsDuplicateQuestItem()
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->assignSkill($this->skill)->trainSkill('Astral Magics');

        $this->rewards['Level 1']['Dead Priest-NIy5tZ00Bl']['items'][] = [
            'id' => $this->questItem->id,
            'name' => $this->questItem->affix_name,
        ];

        $rewards = AdventureCompletedRewards::CombineRewards($this->rewards, $character->getCharacter(false));

        $this->assertGreaterThan(0, $rewards['exp']);
        $this->assertGreaterThan(0, $rewards['gold']);
        $this->assertGreaterThan(0, $rewards['skill']['exp']);
        $this->assertEquals(0.1, $rewards['skill']['exp_towards']);
        $this->assertEquals('Astral Magics', $rewards['skill']['skill_name']);
        $this->assertNotEmpty($rewards['items']);

    }

    public function testBaselineProcessRewardsWhenNoItems()
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->assignSkill($this->skill)->trainSkill('Astral Magics');

        $this->rewards['Level 1']['Dead Priest-NIy5tZ00Bl']['items'] = [];
        $this->rewards['Level 1']['Goblin-N0Km0lwpDy']['items'] = [];

        $rewards = AdventureCompletedRewards::CombineRewards($this->rewards, $character->getCharacter(false));

        $this->assertGreaterThan(0, $rewards['exp']);
        $this->assertGreaterThan(0, $rewards['gold']);
        $this->assertGreaterThan(0, $rewards['skill']['exp']);
        $this->assertEquals(0.1, $rewards['skill']['exp_towards']);
        $this->assertEquals('Astral Magics', $rewards['skill']['skill_name']);
        $this->assertNotEmpty($rewards['items']);

    }

    public function testBaselineProcessRewardsWhenNoSkill()
    {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->assignSkill($this->skill)->trainSkill('Astral Magics');

        $rewards = [
            'Level 1' =>
                [
                    'Goblin-N0Km0lwpDy' =>
                        [
                            'exp' => 3,
                            'gold' => 25,
                            'items' =>
                                [
                                    0 =>
                                        [
                                            'id' => $this->item->id,
                                            'name' => $this->item->affix_name,
                                        ],
                                ],
                        ],
                    'Dead Priest-NIy5tZ00Bl' =>
                        [
                            'exp' => 3,
                            'gold' => 50,
                            'items' =>
                                [
                                    0 =>
                                        [
                                            'id' => $this->questItem->id,
                                            'name' => $this->questItem->affix_name,
                                        ],
                                ],
                        ],
                ],
        ];


        $rewards = AdventureCompletedRewards::CombineRewards($rewards, $character->getCharacter(false));

        $this->assertGreaterThan(0, $rewards['exp']);
        $this->assertGreaterThan(0, $rewards['gold']);
        $this->assertNotEmpty($rewards['items']);

    }

    public function testPlayerCannotHaveItem()
    {
        $character = (new CharacterFactory())->createBaseCharacter()
                                             ->givePlayerLocation()
                                             ->assignSkill($this->skill)
                                             ->inventoryManagement()->giveItem($this->questItem)
                                             ->getCharacterFactory()
                                             ->trainSkill('Astral Magics');

        $rewards = AdventureCompletedRewards::CombineRewards($this->rewards, $character->getCharacter(false));

        $this->assertGreaterThan(0, $rewards['exp']);
        $this->assertGreaterThan(0, $rewards['gold']);
        $this->assertGreaterThan(0, $rewards['skill']['exp']);
        $this->assertEquals(0.1, $rewards['skill']['exp_towards']);
        $this->assertEquals('Astral Magics', $rewards['skill']['skill_name']);
        $this->assertNotEmpty($rewards['items']);

    }

    public function testCharacterDeadInMessages() {
        $hasMessage = AdventureCompletedRewards::messagesHasPlayerDeath([
            [
                'message' => 'You have died during the fight! Death has come for you!'
            ]
        ]);

        $this->assertTrue($hasMessage);
    }

    public function testCharacterNotDeadInMessages() {
        $hasMessage = AdventureCompletedRewards::messagesHasPlayerDeath([
            [
                'message' => 'Sample'
            ]
        ]);

        $this->assertFalse($hasMessage);
    }
}
