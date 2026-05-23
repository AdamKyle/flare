<?php

namespace Tests\Unit\Game\Core\Values;

use App\Game\Core\Values\LevelUpValue;
use App\Game\Reincarnate\Values\MaxReincarnationStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class LevelUpValueTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function testLevelUpWithoutBoonIncreasesLevelAndStatsByOneLevel(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character);

        $this->assertSame(2, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(2, $levelUpValue['dur']);
        $this->assertSame(3, $levelUpValue['dex']);
        $this->assertSame(2, $levelUpValue['chr']);
        $this->assertSame(2, $levelUpValue['int']);
        $this->assertSame(2, $levelUpValue['agi']);
        $this->assertSame(2, $levelUpValue['focus']);
    }

    public function testStackableExtraLevelBoonIncreasesStatsByActualLevelsGained(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Stackable Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(6, $levelUpValue['level']);
        $this->assertSame(6, $levelUpValue['str']);
        $this->assertSame(6, $levelUpValue['dur']);
        $this->assertSame(11, $levelUpValue['dex']);
        $this->assertSame(6, $levelUpValue['chr']);
        $this->assertSame(6, $levelUpValue['int']);
        $this->assertSame(6, $levelUpValue['agi']);
        $this->assertSame(6, $levelUpValue['focus']);
    }

    public function testExtraLevelBoonNearMaxLevelScalesStatsOnlyByActualLevelsGainedAfterClamp(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Near Max Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->update([
            'level' => 999,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(1000, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(2, $levelUpValue['dur']);
        $this->assertSame(3, $levelUpValue['dex']);
        $this->assertSame(2, $levelUpValue['chr']);
        $this->assertSame(2, $levelUpValue['int']);
        $this->assertSame(2, $levelUpValue['agi']);
        $this->assertSame(2, $levelUpValue['focus']);
    }

    public function testMultipleActiveStackableExtraLevelBoonsSumRawStatGains(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $firstBoon = $this->createItem([
            'name' => 'First Stackable Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $secondBoon = $this->createItem([
            'name' => 'Second Stackable Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $firstBoon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 2,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $secondBoon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 3,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(7, $levelUpValue['level']);
        $this->assertSame(7, $levelUpValue['str']);
        $this->assertSame(7, $levelUpValue['dur']);
        $this->assertSame(13, $levelUpValue['dex']);
        $this->assertSame(7, $levelUpValue['chr']);
        $this->assertSame(7, $levelUpValue['int']);
        $this->assertSame(7, $levelUpValue['agi']);
        $this->assertSame(7, $levelUpValue['focus']);
    }

    public function testExtraLevelBoonWithZeroAmountUsedDoesNotBreakRawStatGains(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $zeroAmountBoon = $this->createItem([
            'name' => 'Zero Amount Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $zeroAmountBoon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 0,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(2, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(2, $levelUpValue['dur']);
        $this->assertSame(3, $levelUpValue['dex']);
        $this->assertSame(2, $levelUpValue['chr']);
        $this->assertSame(2, $levelUpValue['int']);
        $this->assertSame(2, $levelUpValue['agi']);
        $this->assertSame(2, $levelUpValue['focus']);
    }

    public function testStatsAtMaxStatCapDoNotIncreasePastProjectMaxStatConstant(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS,
            'dur' => MaxReincarnationStats::MAX_STATS,
            'dex' => MaxReincarnationStats::MAX_STATS,
            'chr' => MaxReincarnationStats::MAX_STATS,
            'int' => MaxReincarnationStats::MAX_STATS,
            'agi' => MaxReincarnationStats::MAX_STATS,
            'focus' => MaxReincarnationStats::MAX_STATS,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['str']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['dur']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['dex']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['chr']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['int']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['agi']);
        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['focus']);
    }

    public function testNormalStatOneBelowMaxStatCapDoesNotIncreasePastProjectMaxStatConstant(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'str' => MaxReincarnationStats::MAX_STATS - 1,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['str']);
    }

    public function testDamageStatOneBelowMaxStatCapDoesNotIncreasePastProjectMaxStatConstant(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $character->update([
            'dex' => MaxReincarnationStats::MAX_STATS - 1,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(MaxReincarnationStats::MAX_STATS, $levelUpValue['dex']);
    }

    public function testCharacterAlreadyAtMaxLevelGainsNoExtraRawStats(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Max Level Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        $character->update([
            'level' => 1000,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(1000, $levelUpValue['level']);
        $this->assertSame(1, $levelUpValue['str']);
        $this->assertSame(1, $levelUpValue['dur']);
        $this->assertSame(1, $levelUpValue['dex']);
        $this->assertSame(1, $levelUpValue['chr']);
        $this->assertSame(1, $levelUpValue['int']);
        $this->assertSame(1, $levelUpValue['agi']);
        $this->assertSame(1, $levelUpValue['focus']);
    }

    public function testExtraLevelBoonWithNullAmountUsedDoesNotBreakRawStatGains(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter(classOptions: ['damage_stat' => 'dex'], assignBaseSkill: false, assignPassiveSkills: false)
            ->getCharacter();

        $boon = $this->createItem([
            'name' => 'Null Amount Extra Level Boon',
            'type' => 'quest',
            'can_stack' => true,
            'gains_additional_level' => true,
        ]);

        DB::statement('ALTER TABLE character_boons MODIFY amount_used INT NULL');

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => null,
        ]);

        $levelUpValue = resolve(LevelUpValue::class)->createValueObject($character->refresh());

        $this->assertSame(2, $levelUpValue['level']);
        $this->assertSame(2, $levelUpValue['str']);
        $this->assertSame(3, $levelUpValue['dex']);
    }
}
