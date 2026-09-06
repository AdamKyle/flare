<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;

class SkillServiceAreaGemBonusTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMapGemParamter, CreateGameSkill, CreateGem, CreateLocation, RefreshDatabase;

    public function test_crafting_skill_bonus_applies_when_skill_matches_gem_crafting_skill_ids(): void
    {
        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::CRAFTING->value,
            'name' => 'Weapon Crafting',
            'max_level' => 400,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($craftingSkill, 10, false, [
            'xp' => 0,
        ])->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter([
            'game_map_id' => $gameMap->id,
            'crafting_skill_ids' => [$craftingSkill->id],
        ]);
        $gem = $this->createMapGeneratedGem($profile, [
            'crafting_skill_bonus' => 1.0,
            'crafting_skill_ids' => [$craftingSkill->id],
        ]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $xpGained = resolve(SkillService::class)->assignXpToCraftingSkill($gameMap->refresh(), $skill);

        $this->assertSame(50, $xpGained);
    }

    public function test_crafting_skill_bonus_does_not_apply_to_an_unrelated_crafting_skill(): void
    {
        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::CRAFTING->value,
            'name' => 'Weapon Crafting',
            'max_level' => 400,
        ]);
        $unrelatedSkill = $this->createGameSkill([
            'type' => SkillTypeValue::CRAFTING->value,
            'name' => 'Alchemy Crafting',
            'max_level' => 400,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($craftingSkill, 10, false, [
            'xp' => 0,
        ])->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter([
            'game_map_id' => $gameMap->id,
            'crafting_skill_ids' => [$unrelatedSkill->id],
        ]);
        $gem = $this->createMapGeneratedGem($profile, [
            'crafting_skill_bonus' => 1.0,
            'crafting_skill_ids' => [$unrelatedSkill->id],
        ]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $xpGained = resolve(SkillService::class)->assignXpToCraftingSkill($gameMap->refresh(), $skill);

        $this->assertSame(25, $xpGained);
    }

    public function test_map_and_location_crafting_bonuses_add_when_they_target_the_same_skill(): void
    {
        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::CRAFTING->value,
            'name' => 'Weapon Crafting',
            'max_level' => 400,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($craftingSkill, 10, false, [
            'xp' => 0,
        ])->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();
        $gameMap = $character->map->gameMap;

        $mapProfile = $this->createGameMapGemParamter([
            'game_map_id' => $gameMap->id,
            'crafting_skill_ids' => [$craftingSkill->id],
        ]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, [
            'crafting_skill_bonus' => 0.5,
            'crafting_skill_ids' => [$craftingSkill->id],
        ]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null, 'x' => $character->map->character_position_x, 'y' => $character->map->character_position_y]);
        $locationProfile = $this->createGameLocationGemParamter([
            'location_id' => $location->id,
            'crafting_skill_ids' => [$craftingSkill->id],
        ]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, [
            'crafting_skill_bonus' => 0.5,
            'crafting_skill_ids' => [$craftingSkill->id],
        ]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $xpGained = resolve(SkillService::class)->assignXpToCraftingSkill($gameMap->refresh(), $skill);

        $this->assertSame(50, $xpGained);
    }
}
