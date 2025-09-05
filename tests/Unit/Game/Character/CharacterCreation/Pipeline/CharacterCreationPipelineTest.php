<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Pipeline;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassRankWeaponMastery;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\GameClass;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Flare\Values\MapNameValue;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CharacterCreationPipelineTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateClass,
        CreateCharacter,
        CreateGameMap,
        CreateItem,
        CreateGameSkill,
        CreatePassiveSkill;

    public function testRunBuildsFullCharacter(): void
    {
        $surface = $this->createGameMap(['name' => MapNameValue::SURFACE]);
        $hell = $this->createGameMap(['name' => MapNameValue::HELL]);
        $this->createGameMap(['name' => MapNameValue::PURGATORY]);

        $this->createItem([
            'name' => 'Rusty blade',
            'type' => 'sword',
            'base_damage' => 3,
            'skill_level_required' => 1,
        ]);

        $generalSkill = $this->createGameSkill(['name' => 'General A', 'game_class_id' => null]);

        $fighter = $this->createClass(['name' => 'Fighter', 'damage_stat' => 'str']);
        $mage = $this->createClass(['name' => 'Mage']);

        $classSkill = $this->createGameSkill(['name' => 'Fighter Skill', 'game_class_id' => $fighter->id]);

        $parentPassive = $this->createPassiveSkill([
            'is_locked' => false,
            'unlocks_at_level' => 0,
            'parent_skill_id' => null,
        ]);

        $childPassive = $this->createPassiveSkill([
            'parent_skill_id' => $parentPassive->id,
            'unlocks_at_level' => 1,
        ]);

        $user = $this->createUser();
        $race = $this->createRace();

        $mock = Mockery::mock(BuildCharacterAttackTypes::class);
        $mock->shouldReceive('buildCache')->once()->with(Mockery::type(Character::class));
        $this->app->instance(BuildCharacterAttackTypes::class, $mock);

        $state = app(CharacterBuildState::class)
            ->setUser($user)
            ->setRace($race)
            ->setClass($fighter)
            ->setMap($surface)
            ->setCharacterName('PipelineUser')
            ->setNow(now());

        $pipeline = app(CharacterCreationPipeline::class);
        $final = $pipeline->run($state);

        $this->assertInstanceOf(CharacterBuildState::class, $final);
        $this->assertInstanceOf(Character::class, $final->getCharacter());

        $character = Character::query()
            ->with(['gemBag', 'inventory.slots', 'map', 'factions', 'classRanks', 'classRanks.weaponMasteries'])
            ->find($final->getCharacter()->id);

        $this->assertNotNull($character);
        $this->assertSame('PipelineUser', $character->name);
        $this->assertNotNull($character->gemBag);
        $this->assertNotNull($character->inventory);

        $this->assertSame(1, $character->inventory->slots->count());
        $slot = $character->inventory->slots->first();
        $this->assertTrue((bool) $slot->equipped);
        $this->assertSame('left-hand', $slot->position);
        $starterId = Item::query()->where('type', 'sword')->where('skill_level_required', 1)->value('id');
        $this->assertSame($starterId, $slot->item_id);

        $this->assertNotNull($character->map);
        $this->assertSame($surface->id, $character->map->game_map_id);

        $this->assertSame(2, $character->factions->count());
        $this->assertTrue($character->factions->pluck('game_map_id')->contains($surface->id));
        $this->assertTrue($character->factions->pluck('game_map_id')->contains($hell->id));

        $skills = Skill::query()->where('character_id', $character->id)->pluck('game_skill_id');
        $this->assertTrue($skills->contains($generalSkill->id));
        $this->assertTrue($skills->contains($classSkill->id));

        $passives = CharacterPassiveSkill::query()
            ->where('character_id', $character->id)
            ->whereIn('passive_skill_id', [$parentPassive->id, $childPassive->id])
            ->get();
        $this->assertSame(2, $passives->count());
        $parentRow = $passives->firstWhere('passive_skill_id', $parentPassive->id);
        $childRow = $passives->firstWhere('passive_skill_id', $childPassive->id);
        $this->assertFalse((bool) $parentRow->is_locked);
        $this->assertTrue((bool) $childRow->is_locked);
        $this->assertSame($parentRow->id, $childRow->parent_skill_id);

        $totalClasses = GameClass::query()->count();
        $this->assertSame($totalClasses, CharacterClassRank::query()->where('character_id', $character->id)->count());

        $weaponTypesCount = count(ItemType::allWeaponTypes());
        $ranks = $character->classRanks;
        $ranks->each(function (CharacterClassRank $rank) use ($weaponTypesCount) {
            $this->assertSame(
                $weaponTypesCount,
                CharacterClassRankWeaponMastery::query()->where('character_class_rank_id', $rank->id)->count()
            );
        });

        $fighterRank = $ranks->firstWhere('game_class_id', $fighter->id);
        $mapping = ItemTypeMapping::getForClass($fighter->name);
        $primaryType = is_array($mapping) ? $mapping[0] : $mapping;
        $fighterPrimary = CharacterClassRankWeaponMastery::query()
            ->where('character_class_rank_id', $fighterRank->id)
            ->where('weapon_type', $primaryType)
            ->first();
        $this->assertNotNull($fighterPrimary);
        $this->assertSame(5, (int) $fighterPrimary->level);
    }

    public function testRunNoOpWithEmptyState(): void
    {
        $mock = Mockery::mock(BuildCharacterAttackTypes::class);
        $mock->shouldReceive('buildCache')->never();
        $this->app->instance(BuildCharacterAttackTypes::class, $mock);

        $state = app(CharacterBuildState::class)->setNow(now());

        $pipeline = app(CharacterCreationPipeline::class);
        $final = $pipeline->run($state);

        $this->assertInstanceOf(CharacterBuildState::class, $final);
        $this->assertNull($final->getCharacter());
        $this->assertSame(0, Character::query()->count());
        $this->assertSame(0, Skill::query()->count());
        $this->assertSame(0, CharacterPassiveSkill::query()->count());
        $this->assertSame(0, CharacterClassRank::query()->count());
        $this->assertSame(0, CharacterClassRankWeaponMastery::query()->count());
    }
}
