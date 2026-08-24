<?php

namespace Tests\Unit\Flare\Transformers;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMaxLevelConfiguration;

class BaseTransformerTest extends TestCase
{
    use CreateInventorySlot, CreateItem, CreateMaxLevelConfiguration, RefreshDatabase;

    public function test_fetch_attack_types_returns_empty_array_when_not_cached(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::forget('character-attack-data-'.$character->id);

        $this->assertSame([], (new BaseTransformer())->fetchAttackTypes($character));
    }

    public function test_fetch_attack_types_returns_cached_attack_types(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-attack-data-'.$character->id, ['attack_types' => ['weapon']]);

        $this->assertSame(['weapon'], (new BaseTransformer())->fetchAttackTypes($character));
    }

    public function test_fetch_stats_returns_zero_when_not_cached(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::forget('character-attack-data-'.$character->id);

        $this->assertSame(0.0, (new BaseTransformer())->fetchStats($character, 'str'));
    }

    public function test_fetch_stats_returns_cached_stat(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-attack-data-'.$character->id, ['character_data' => ['str' => 42]]);

        $this->assertSame(42, (new BaseTransformer())->fetchStats($character, 'str'));
    }

    public function test_fetch_stat_affixes_returns_empty_array_when_not_cached(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::forget('character-attack-data-'.$character->id);

        $this->assertSame([], (new BaseTransformer())->fetchStatAffixes($character));
    }

    public function test_fetch_stat_affixes_returns_cached_stat_affixes(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-attack-data-'.$character->id, ['stat_affixes' => ['cant_be_resisted' => true]]);

        $this->assertSame(['cant_be_resisted' => true], (new BaseTransformer())->fetchStatAffixes($character));
    }

    public function test_fetch_skills_returns_empty_array_when_not_cached(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::forget('character-attack-data-'.$character->id);

        $this->assertSame([], (new BaseTransformer())->fetchSkills($character));
    }

    public function test_fetch_skills_returns_sorted_cached_skills(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        Cache::put('character-attack-data-'.$character->id, ['skills' => ['Smithing', 'Alchemy']]);

        $this->assertSame(['Alchemy', 'Smithing'], (new BaseTransformer())->fetchSkills($character));
    }

    public function test_is_alchemy_locked_true_when_no_alchemy_game_skill_exists(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $alchemyGameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();
        Skill::where('game_skill_id', $alchemyGameSkill->id)->delete();
        $alchemyGameSkill->delete();

        $this->assertTrue((new BaseTransformer())->isAlchemyLocked($character));
    }

    public function test_is_alchemy_locked_true_when_the_character_has_no_alchemy_skill_row(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $alchemyGameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();
        Skill::where('game_skill_id', $alchemyGameSkill->id)->where('character_id', $character->id)->delete();

        $this->assertTrue((new BaseTransformer())->isAlchemyLocked($character));
    }

    public function test_is_alchemy_locked_reflects_the_characters_skill_lock_state(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $alchemyGameSkill = GameSkill::where('type', SkillTypeValue::ALCHEMY->value)->first();
        Skill::where('game_skill_id', $alchemyGameSkill->id)->where('character_id', $character->id)->update(['is_locked' => true]);

        $this->assertTrue((new BaseTransformer())->isAlchemyLocked($character));
    }

    public function test_get_max_level_returns_default_max_level_when_no_continue_leveling_item_exists(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $this->assertSame(MaxLevel::MAX_LEVEL, (new BaseTransformer())->getMaxLevel($character));
    }

    public function test_get_max_level_returns_default_max_level_when_character_does_not_own_the_item(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $this->createItem(['effect' => ItemEffectType::CONTINUE_LEVELING->value]);

        $this->assertSame(MaxLevel::MAX_LEVEL, (new BaseTransformer())->getMaxLevel($character));
    }

    public function test_get_max_level_returns_configured_max_level_when_character_owns_the_item(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $item = $this->createItem(['effect' => ItemEffectType::CONTINUE_LEVELING->value]);
        $this->createInventorySlot([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);
        $this->createMaxLevelConfiguration(['max_level' => 150]);

        $this->assertSame(150, (new BaseTransformer())->getMaxLevel($character));
    }
}
