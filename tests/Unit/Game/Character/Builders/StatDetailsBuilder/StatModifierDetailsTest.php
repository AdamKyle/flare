<?php

namespace Tests\Unit\Game\Character\Builders\StatDetailsBuilder;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Character\Builders\StatDetailsBuilder\StatModifierDetails;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class StatModifierDetailsTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix, CreateGameClassSpecial, CreateClass;

    private ?CharacterFactory $character;

    private ?StatModifierDetails $statModifierDetails;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'damage_stat' => 'dur',
            'to_hit_stat' => 'dur',
        ]))->givePlayerLocation();

        $this->statModifierDetails = resolve(StatModifierDetails::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->statModifierDetails = null;
    }

    public function testGetDetailsForStat() {
        $character = $this->createCharacterForData($this->character);

        GameMap::find($character->map->game_map_id)->update([
            'character_attack_reduction' => .20,
            'name' => MapNameValue::HELL,
        ]);

        $data = $this->statModifierDetails->setCharacter($character)->forStat('dur');

        $this->assertGreaterThan(0, $data['base_value']);
        $this->assertNotEmpty($data['items_equipped']);
        $this->assertNotNull($data['class_specialties']);
        $this->assertNotNull($data['ancestral_item_skill_data']);
        $this->assertNotNull($data['map_reduction']);
    }

    public function testGetDetailsForStatWhenOnEventMap() {
        $character = $this->createCharacterForData($this->character);

        GameMap::find($character->map->game_map_id)->update([
            'character_attack_reduction' => .20,
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
        ]);

        $data = $this->statModifierDetails->setCharacter($character)->forStat('dur');

        $this->assertGreaterThan(0, $data['base_value']);
        $this->assertNotEmpty($data['items_equipped']);
        $this->assertNotNull($data['class_specialties']);
        $this->assertNotNull($data['ancestral_item_skill_data']);
        $this->assertNotNull($data['map_reduction']);
    }

    public function testGetDetailsForStatWhenOnNormalMapAndNaked() {
        $character = $this->character->getCharacter();

        $data = $this->statModifierDetails->setCharacter($character)->forStat('dur');

        dump($data['class_specialties']);

        $this->assertGreaterThan(0, $data['base_value']);
        $this->assertEmpty($data['items_equipped']);
        $this->assertNull($data['class_specialties']);
        $this->assertNull($data['ancestral_item_skill_data']);
        $this->assertNull($data['map_reduction']);
    }

    private function createCharacterForData(CharacterFactory $characterFactory): Character {

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'dur_mod' => 1.0,
                'base_damage_mod' => 1.0,
                'base_healing_mod' => 1.0,
                'base_ac_mod' => 1.0,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'dur_mod' => 1.0,
                'base_damage_mod' => 1.0,
                'base_healing_mod' => 1.0,
                'base_ac_mod' => 1.0,
            ])->id,
            'base_damage' => 10,
            'dur_mod' => .10,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => $item->id,
            'devouring_darkness_bonus' => 1.0,
            'stat_increase_bonus' => 2.0,
        ]);

        $item = $item->refresh();

        $artifact = $this->createItem([
            'type' => 'artifact',
            'dur_mod' => .10,
            'base_damage' => 10,
        ]);

        $itemSkill = $artifact->itemSkill()->create([
            'name' => 'Sample',
            'description' => 'test',
            'str_mod' => 1.0,
            'dex_mod' => 1.0,
            'dur_mod' => 1.0,
            'chr_mod' => 1.0,
            'focus_mod' => 1.0,
            'int_mod' => 1.0,
            'agi_mod' => 1.0,
            'base_damage_mod' => 1.0,
            'base_ac_mod' => 1.0,
            'base_healing_mod' => 1.0,
            'max_level' => 1.0,
            'total_kills_needed' => 1.0,
            'parent_id' => 1.0,
            'parent_level_needed' => 1.0,
        ]);

        $artifact = $artifact->refresh();

        $artifact->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 10,
            'current_kill' => 100,
            'is_training' => true,
        ]);

        $artifact = $artifact->refresh();

        $character = $characterFactory->inventoryManagement()
                        ->giveItem(
                            $item, true, 'left-hand'
                        )
                        ->giveItem(
                            $this->createItem([
                                'type' => 'weapon',
                                'item_suffix_id' => $this->createItemAffix([
                                    'type' => 'suffix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'item_prefix_id' => $this->createItemAffix([
                                    'type' => 'prefix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'base_damage' => 10,
                                'dur_mod' => .10,
                            ]), true, 'right-hand'
                        )
                        ->giveItem(
                            $this->createItem([
                                'type' => 'spell-damage',
                                'item_suffix_id' => $this->createItemAffix([
                                    'type' => 'suffix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'item_prefix_id' => $this->createItemAffix([
                                    'type' => 'prefix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'base_damage' => 10,
                            ]), true, 'spell-one'
                        )
                        ->giveItem(
                            $this->createItem([
                                'type' => 'spell-healing',
                                'item_suffix_id' => $this->createItemAffix([
                                    'type' => 'suffix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'item_prefix_id' => $this->createItemAffix([
                                    'type' => 'prefix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'base_healing' => 10,
                            ]), true, 'spell-two'
                        )
                        ->giveItem(
                            $this->createItem([
                                'type' => 'body',
                                'item_suffix_id' => $this->createItemAffix([
                                    'type' => 'suffix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'item_prefix_id' => $this->createItemAffix([
                                    'type' => 'prefix',
                                    'dur_mod' => 1.0,
                                    'base_damage_mod' => 1.0,
                                    'base_healing_mod' => 1.0,
                                    'base_ac_mod' => 1.0,
                                ])->id,
                                'base_ac' => 10,
                                'dur_mod' => .20,
                            ]), true, 'body'
                        )
                        ->giveItem(
                            $this->createItem([
                                'type' => 'ring',
                                'base_healing' => 10,
                            ]), true, 'ring-one'
                        )
                        ->giveItem(
                            $this->createItem([
                                'type' => 'quest',
                                'effect' => ItemEffectsValue::PURGATORY,
                            ])
                        )
                        ->giveItem($artifact, true, 'artifact')
                        ->getCharacterFactory()
                        ->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'name' => 'sample',
            'description' => 'sample',
            'requires_class_rank_level' => 0,
            'specialty_damage' => 1000,
            'increase_specialty_damage_per_level' => 500,
            'specialty_damage_uses_damage_stat_amount' => .10,
            'base_damage_mod' => 1.0,
            'base_ac_mod' => 1.0,
            'base_healing_mod' => 1.0,
            'base_spell_damage_mod' => 1.0,
            'health_mod' => 1.0,
            'base_damage_stat_increase' => 1.0,
            'attack_type_required' => 1.0,
            'spell_evasion' => 1.0,
            'affix_damage_reduction' => 1.0,
            'healing_reduction' => 1.0,
            'skill_reduction' => 1.0,
            'resistance_reduction' => 1.0,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 10,
            'current_xp' => 15,
            'required_xp' => 1000000,
            'equipped' => true
        ]);

        $character = $character->refresh();

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $this->createItem([
                'base_damage_mod' => 3.0,
                'base_healing_mod' => 3.0,
                'base_ac_mod' => 3.0,
                'increase_stat_by' => 3.0,
            ])->id,
            'last_for_minutes' => 600,
            'amount_used' => 10,
            'started' => now(),
            'complete' => now()->addHours(5),
        ]);

        return $character->refresh();
    }
}
