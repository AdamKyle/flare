<?php

namespace Tests\Unit\Game\Gems\Transformers;

use App\Flare\Models\Gem;
use App\Game\Gems\Transformers\RolledGemTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;

class RolledGemTransformerTest extends TestCase
{
    use CreateGameSkill, CreateGem, RefreshDatabase;

    private RolledGemTransformer $rolledGemTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolledGemTransformer = new RolledGemTransformer;
    }

    public function test_transform_returns_every_factual_rolled_gem_field(): void
    {
        $gem = $this->createGem([
            'name' => 'Fiery Ember',
            'domain' => Gem::DOMAIN_MAP,
            'roll_number' => 3,
            'monster_atonement' => 1,
            'monster_atonement_amount' => 0.15,
            'character_xp_bonus' => 0.1,
            'character_class_rank_xp_bonus' => 0.11,
            'kingdom_passive_training_reduction' => 0.12,
            'gold_gain' => 0.13,
            'gold_dust_gain' => 0.14,
            'shards_gain' => 0.16,
            'copper_coin_gain' => 0.17,
            'character_class_specialty_xp_gain' => 0.18,
            'crafting_skill_bonus' => 0.19,
            'item_drop_chance_increase' => 0.2,
            'unique_item_drop_chance_increase' => 0.21,
            'mythic_item_drop_chance_increase' => 0.22,
            'cosmic_item_drop_chance_increase' => 0.23,
            'character_power_reduction' => 0.24,
            'enemy_strength_increase' => 0.25,
            'enemy_healing_increase' => 0.26,
            'enemy_spell_evasion' => 0.27,
            'enemy_affix_resistance' => 0.28,
            'enemy_entrancing_chance' => 0.29,
            'enemy_devouring_light_chance' => 0.3,
            'enemy_devouring_darkness_chance' => 0.31,
            'enemy_ambush_chance' => 0.32,
            'enemy_ambush_resistance' => 0.33,
            'enemy_counter_chance' => 0.34,
            'enemy_counter_resistance' => 0.35,
            'enemy_quest_item_drop_chance_increase' => 0.36,
            'monster_xp_increase' => 0.37,
            'monster_gold_drop_increase' => 0.38,
        ]);

        $transformed = $this->rolledGemTransformer->transform($gem, true);

        $this->assertSame([
            'id' => $gem->id,
            'name' => 'Fiery Ember',
            'domain' => Gem::DOMAIN_MAP,
            'roll_number' => 3,
            'is_active' => true,
            'crafting_skills' => [],
            'monster_atonement' => 1,
            'monster_atonement_amount' => 0.15,
            'character_xp_bonus' => 0.1,
            'character_class_rank_xp_bonus' => 0.11,
            'kingdom_passive_training_reduction' => 0.12,
            'gold_gain' => 0.13,
            'gold_dust_gain' => 0.14,
            'shards_gain' => 0.16,
            'copper_coin_gain' => 0.17,
            'character_class_specialty_xp_gain' => 0.18,
            'crafting_skill_bonus' => 0.19,
            'item_drop_chance_increase' => 0.2,
            'unique_item_drop_chance_increase' => 0.21,
            'mythic_item_drop_chance_increase' => 0.22,
            'cosmic_item_drop_chance_increase' => 0.23,
            'character_power_reduction' => 0.24,
            'enemy_strength_increase' => 0.25,
            'enemy_healing_increase' => 0.26,
            'enemy_spell_evasion' => 0.27,
            'enemy_affix_resistance' => 0.28,
            'enemy_entrancing_chance' => 0.29,
            'enemy_devouring_light_chance' => 0.3,
            'enemy_devouring_darkness_chance' => 0.31,
            'enemy_ambush_chance' => 0.32,
            'enemy_ambush_resistance' => 0.33,
            'enemy_counter_chance' => 0.34,
            'enemy_counter_resistance' => 0.35,
            'enemy_quest_item_drop_chance_increase' => 0.36,
            'monster_xp_increase' => 0.37,
            'monster_gold_drop_increase' => 0.38,
        ], $transformed);
    }

    public function test_transform_returns_crafting_skills_sorted_by_name_as_compact_identities(): void
    {
        $zetaSkill = $this->createGameSkill(['name' => 'Zeta Skill']);
        $alphaSkill = $this->createGameSkill(['name' => 'Alpha Skill']);

        $gem = $this->createGem(['crafting_skill_ids' => [$zetaSkill->id, $alphaSkill->id]]);

        $transformed = $this->rolledGemTransformer->transform($gem, false);

        $this->assertSame([
            ['id' => $alphaSkill->id, 'name' => 'Alpha Skill'],
            ['id' => $zetaSkill->id, 'name' => 'Zeta Skill'],
        ], $transformed['crafting_skills']);
    }

    public function test_transform_uses_the_exact_supplied_is_active_boolean(): void
    {
        $gem = $this->createGem();

        $this->assertFalse($this->rolledGemTransformer->transform($gem, false)['is_active']);
        $this->assertTrue($this->rolledGemTransformer->transform($gem, true)['is_active']);
    }
}
