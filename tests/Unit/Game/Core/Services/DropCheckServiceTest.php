<?php

namespace Tests\Unit\Game\Core\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Values\CelestialType;
use App\Flare\Values\LocationType;
use App\Game\Core\Services\DropCheckService;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class DropCheckServiceTest extends TestCase
{
    use CreateCharacterAutomation, CreateItem, CreateItemAffix, CreateLocation, CreateMonster, RefreshDatabase;

    private ?DropCheckService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(DropCheckService::class);
    }

    public function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function testProcessUsesDropCheckChanceWhenNotAtSpecialLocationAndNoGameMapBonus(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->withArgs(function ($passedMonster, $level, $lootingChance, $gameMapBonus) {
                return $level === 1
                    && abs($lootingChance - 0.10) < 0.00001
                    && abs($gameMapBonus - 0.0) < 0.00001
                    && !is_null($passedMonster)
                    && $passedMonster->id > 0;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testProcessSetsGameMapBonusWhenPresent(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->withArgs(function ($passedMonster, $level, $lootingChance, $gameMapBonus) {
                return $level === 1
                    && abs($lootingChance - 0.10) < 0.00001
                    && abs($gameMapBonus - 0.25) < 0.00001
                    && !is_null($passedMonster)
                    && $passedMonster->id > 0;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $character->map->gameMap->update([
            'drop_chance_bonus' => 0.25,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testProcessUsesDifficultItemChanceWhenAtSpecialLocationAndClampsLootingChanceAtPointFourFive(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.45) < 0.00001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 1.0);

        $this->createSpecialLocation($character, null);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testProcessHandlesKingCelestialMythicDropAndClampsLootingChanceAtPointOneFive(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance) {
                return abs($chance - 0.15) < 0.00001;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 1.0);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'celestial_type' => CelestialType::KING_CELESTIAL,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testProcessHandlesKingCelestialMythicDropWithoutClampingWhenUnderCap(): void
    {
        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->once()
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance) {
                return abs($chance - 0.10) < 0.00001;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'celestial_type' => CelestialType::KING_CELESTIAL,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testProcessAwardsMythicItemInPurgatoryDungeonsWhenNoAutomations(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->withAnyArgs()->zeroOrMoreTimes()->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->withAnyArgs()->zeroOrMoreTimes()->andReturn(1);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.30) < 0.00001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withNoArgs()
            ->andReturnTrue();

        $this->createItemAffix(['type' => 'prefix']);
        $this->createItemAffix(['type' => 'suffix']);

        $this->createItem([
            'specialty_type' => null,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
            'is_mythic' => false,
        ]);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.30);

        $this->createSpecialLocation($character, LocationType::PURGATORY_DUNGEONS);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $character = $character->refresh();
        $afterSlots = $character->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 1, $afterSlots);

        $newItem = $character->inventory->slots()->latest('id')->first()->item;

        $this->assertTrue($newItem->is_mythic);
        $this->assertNotNull($newItem->item_prefix_id);
        $this->assertNotNull($newItem->item_suffix_id);
    }

    public function testProcessDoesNotAwardMythicItemInPurgatoryDungeonsWhenAutomationsRunning(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.30) < 0.00001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.30);

        $this->createSpecialLocation($character, LocationType::PURGATORY_DUNGEONS);

        $this->createExploringAutomation([
            'character_id' => $character->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testProcessUsesCachedLocationWithEffectWhenKeyDoesNotChange(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->twice()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.10) < 0.00001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        DropCheckCalculator::shouldReceive('fetchDropCheckChance')
            ->never();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $this->setLootingToBonus($characterFactory->getCharacter(), 0.10);

        $specialLocation = $this->createSpecialLocation($character, null);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'quest_item_id' => null,
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $this->service?->process($character->refresh(), $monster->refresh());

        $specialLocation->delete();

        $character = $character->refresh()->load('skills.baseSkill', 'map.gameMap', 'currentAutomations');

        $this->service?->process($character->refresh(), $monster->refresh());

        $afterSlots = $character->refresh()->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }


    private function setLootingToBonus(Character $character, float $targetBonus): Character
    {
        $lootingSkill = $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('name', 'Looting');
        })->first();

        if (is_null($lootingSkill)) {
            return $character->refresh()->load('skills.baseSkill', 'map.gameMap', 'currentAutomations');
        }

        $baseSkill = $lootingSkill->baseSkill;

        if (!is_null($baseSkill)) {
            $baseSkill->skill_bonus_per_level = 0.01;
            $baseSkill->max_level = 100;
            $baseSkill->save();
        }

        $perLevel = 0.01;
        $maxLevel = !is_null($baseSkill) ? (int) $baseSkill->max_level : 100;

        if ($targetBonus >= 1.0) {
            $targetLevel = $maxLevel;
        } else {
            $targetLevel = (int) round(($targetBonus / $perLevel) + 1);

            if ($targetLevel < 1) {
                $targetLevel = 1;
            }

            if ($targetLevel >= $maxLevel) {
                $targetLevel = $maxLevel - 1;
            }
        }

        $lootingSkill->update([
            'level' => $targetLevel,
        ]);

        $character = $character->refresh()->load('skills.baseSkill', 'map.gameMap', 'currentAutomations');

        $computed = $character->skills->where('name', 'Looting')->first()?->skill_bonus ?? 0.0;

        if (abs($computed - $targetBonus) > 0.00001 && $targetBonus < 1.0) {
            throw new \Exception('Looting bonus did not match target. Got: ' . $computed . ' Target: ' . $targetBonus);
        }

        return $character;
    }

    private function createSpecialLocation(Character $character, ?int $type): Location
    {
        $map = $character->map->refresh();

        return $this->createLocation([
            'game_map_id' => $map->game_map_id,
            'x' => $map->character_position_x,
            'y' => $map->character_position_y,
            'type' => $type,
            'enemy_strength_type' => 1,
            'name' => 'special_location_' . uniqid('', true),
        ]);
    }
}
