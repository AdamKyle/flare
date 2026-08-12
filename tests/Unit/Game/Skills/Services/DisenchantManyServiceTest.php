<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Skills\Services\DisenchantManyService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DisenchantManyServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?DisenchantManyService $disenchantManyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->disenchantManyService = resolve(DisenchantManyService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->disenchantManyService = null;
    }

    public function test_disenchant_many_returns_no_eligible_items_when_nothing_matches(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->disenchantManyService->disenchantMany(
            resolve(Manager::class),
            resolve(CharacterInventoryCountTransformer::class),
            $character,
            []
        );

        $this->assertSame(200, $result['status']);
        $this->assertSame('No eligible items to disenchant.', $result['message']);
        $this->assertSame([], $result['disenchanted_item']);
    }

    public function test_disenchant_many_ignores_items_without_affixes(): void
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->disenchantManyService->disenchantMany(
            resolve(Manager::class),
            resolve(CharacterInventoryCountTransformer::class),
            $character,
            []
        );

        $this->assertSame('No eligible items to disenchant.', $result['message']);
        $this->assertSame(1, $character->inventory->slots()->count());
    }

    public function test_disenchant_many_succeeds_and_removes_processed_slots(): void
    {
        $prefix = $this->createItemAffix();
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $baseSkill = $this->createGameSkill(['type' => SkillTypeValue::DISENCHANTING, 'skill_bonus_per_level' => 0.0]);
        $character = $this->character
            ->inventoryManagement()->giveItem($item)
            ->getCharacterFactory()
            ->assignSkill($baseSkill, 1)
            ->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 400)->andReturn(400);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 400)->andReturn(1);

        $disenchantManyService = new DisenchantManyService(
            new SkillCheckService($randomNumberGenerator),
            resolve(RandomNumberGenerator::class),
            resolve(ChanceCalculator::class),
        );

        $result = $disenchantManyService->disenchantMany(
            resolve(Manager::class),
            resolve(CharacterInventoryCountTransformer::class),
            $character,
            ['ids' => [$slotId]]
        );

        $this->assertSame(200, $result['status']);
        $this->assertCount(1, $result['disenchanted_item']);
        $this->assertSame('passed', $result['disenchanted_item'][0]['status']);
        $this->assertGreaterThan(0, $result['disenchanted_item'][0]['gold_dust']);
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
        $this->assertGreaterThan(0, $character->fresh()->gold_dust);
    }

    public function test_disenchant_many_records_a_failed_attempt_with_minimum_gold_dust(): void
    {
        $prefix = $this->createItemAffix();
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $baseSkill = $this->createGameSkill(['type' => SkillTypeValue::DISENCHANTING, 'skill_bonus_per_level' => 0.0]);
        $character = $this->character
            ->inventoryManagement()->giveItem($item)
            ->getCharacterFactory()
            ->assignSkill($baseSkill, 1)
            ->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 400)->andReturn(1);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 400)->andReturn(400);

        $disenchantManyService = new DisenchantManyService(
            new SkillCheckService($randomNumberGenerator),
            resolve(RandomNumberGenerator::class),
            resolve(ChanceCalculator::class),
        );

        $result = $disenchantManyService->disenchantMany(
            resolve(Manager::class),
            resolve(CharacterInventoryCountTransformer::class),
            $character,
            ['ids' => [$slotId]]
        );

        $this->assertSame('failed', $result['disenchanted_item'][0]['status']);
        $this->assertSame(1, $result['disenchanted_item'][0]['gold_dust']);
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
    }

    public function test_disenchant_many_excludes_specified_slots(): void
    {
        $prefix = $this->createItemAffix();
        $keepItem = $this->createItem(['item_prefix_id' => $prefix->id]);
        $destroyItem = $this->createItem(['item_prefix_id' => $prefix->id]);
        $baseSkill = $this->createGameSkill(['type' => SkillTypeValue::DISENCHANTING, 'skill_bonus_per_level' => 2.0, 'max_level' => 10]);
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($keepItem)
            ->giveItem($destroyItem)
            ->getCharacterFactory()
            ->assignSkill($baseSkill, 5)
            ->getCharacter();
        $keepSlotId = $character->inventory->slots()->where('item_id', $keepItem->id)->first()->id;
        $destroySlotId = $character->inventory->slots()->where('item_id', $destroyItem->id)->first()->id;

        $result = $this->disenchantManyService->disenchantMany(
            resolve(Manager::class),
            resolve(CharacterInventoryCountTransformer::class),
            $character,
            ['exclude' => [$keepSlotId]]
        );

        $this->assertCount(1, $result['disenchanted_item']);
        $this->assertSame(1, $character->inventory->slots()->where('id', $keepSlotId)->count());
        $this->assertSame(0, $character->inventory->slots()->where('id', $destroySlotId)->count());
    }

    public function test_disenchant_many_awards_zero_gold_dust_once_the_cap_is_reached(): void
    {
        $prefix = $this->createItemAffix();
        $firstItem = $this->createItem(['item_prefix_id' => $prefix->id]);
        $secondItem = $this->createItem(['item_prefix_id' => $prefix->id]);
        $baseSkill = $this->createGameSkill(['type' => SkillTypeValue::DISENCHANTING, 'skill_bonus_per_level' => 0.0]);
        $character = $this->character
            ->inventoryManagement()->giveItem($firstItem)->giveItem($secondItem)
            ->getCharacterFactory()
            ->assignSkill($baseSkill, 1)
            ->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST - 1]);
        $firstSlotId = $character->inventory->slots()->where('item_id', $firstItem->id)->first()->id;
        $secondSlotId = $character->inventory->slots()->where('item_id', $secondItem->id)->first()->id;

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1, 400, 1);

        $disenchantManyService = new DisenchantManyService(
            new SkillCheckService($randomNumberGenerator),
            resolve(RandomNumberGenerator::class),
            resolve(ChanceCalculator::class),
        );

        $result = $disenchantManyService->disenchantMany(
            resolve(Manager::class),
            resolve(CharacterInventoryCountTransformer::class),
            $character->fresh(),
            ['ids' => [$firstSlotId, $secondSlotId]]
        );

        $this->assertCount(2, $result['disenchanted_item']);
        $awardedAmounts = array_column($result['disenchanted_item'], 'gold_dust');
        $this->assertContains(0, $awardedAmounts);
        $this->assertSame(CurrencyLimit::MAX_GOLD_DUST, $character->fresh()->gold_dust);
    }

    public function test_disenchant_many_awards_interest_bonus_when_the_interest_roll_passes(): void
    {
        $prefix = $this->createItemAffix();
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $baseSkill = $this->createGameSkill(['type' => SkillTypeValue::DISENCHANTING, 'skill_bonus_per_level' => 0.0]);
        $character = $this->character
            ->inventoryManagement()->giveItem($item)
            ->getCharacterFactory()
            ->assignSkill($baseSkill, 1)
            ->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 400)->andReturn(400);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 400)->andReturn(1);
        $randomNumberGenerator->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);

        $disenchantManyService = Mockery::mock(
            DisenchantManyService::class,
            [new SkillCheckService($randomNumberGenerator), $randomNumberGenerator, resolve(ChanceCalculator::class)]
        )->makePartial();
        $disenchantManyService->shouldAllowMockingProtectedMethods();
        $disenchantManyService->shouldReceive('passesInterest')->once()->andReturn(true);

        $result = $disenchantManyService->disenchantMany(
            resolve(Manager::class),
            resolve(CharacterInventoryCountTransformer::class),
            $character,
            ['ids' => [$slotId]]
        );

        $this->assertSame('passed', $result['disenchanted_item'][0]['status']);
        // Base award was 1000, +5% interest brings the character's stored total to 1050.
        $this->assertSame(1050, $character->fresh()->gold_dust);
    }
}
