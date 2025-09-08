<?php

namespace Tests\Unit\Flare\Items\Builders;

use App\Flare\Items\Builders\BuildMythicItem;
use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class BuildMythicItemTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix;

    private ?BuildMythicItem $builder = null;
    private ?CharacterFactory $characterFactory = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $randomAffixGenerator = Mockery::mock(RandomAffixGenerator::class)->makePartial();
        $this->app->instance(RandomAffixGenerator::class, $randomAffixGenerator);

        $this->builder = $this->app->make(BuildMythicItem::class);
    }

    public function tearDown(): void
    {
        $this->builder = null;
        $this->characterFactory = null;

        parent::tearDown();
    }

    public function testFetchMythicItemBuildsDuplicateWithAffixesAndMythicFlag(): void
    {
        $character = $this->characterFactory->getCharacter()->refresh();

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'name' => 'Blazing',
            'skill_level_required' => 1,
        ]);

        $suffix = $this->createItemAffix([
            'type' => 'suffix',
            'name' => 'Of Embers',
            'skill_level_required' => 1,
        ]);

        $allowedBase = $this->createItem([
            'type' => 'ring',
            'name' => 'Plain Ring',
            'skill_level_required' => 1,
        ]);

        $randomAffixGenerator = $this->app->make(RandomAffixGenerator::class);
        $randomAffixGenerator->shouldReceive('setCharacter')->andReturnSelf();
        $randomAffixGenerator->shouldReceive('setPaidAmount')->andReturnSelf();
        $randomAffixGenerator->shouldReceive('generateAffix')->with('prefix')->andReturn($prefix);
        $randomAffixGenerator->shouldReceive('generateAffix')->with('suffix')->andReturn($suffix);

        $result = $this->builder->fetchMythicItem($character);

        $this->assertInstanceOf(Item::class, $result);
        $this->assertSame('ring', $result->type);
        $this->assertNotSame($allowedBase->id, $result->id);
        $this->assertSame($prefix->id, $result->item_prefix_id);
        $this->assertSame($suffix->id, $result->item_suffix_id);
        $this->assertTrue((bool) $result->is_mythic);
    }

    public function testFetchMythicItemThrowsWhenAffixGeneratorFails(): void
    {
        $character = $this->characterFactory->getCharacter()->refresh();

        $this->createItem([
            'type' => 'ring',
            'name' => 'Plain Ring',
            'skill_level_required' => 1,
        ]);

        $randomAffixGenerator = $this->app->make(RandomAffixGenerator::class);
        $randomAffixGenerator->shouldReceive('setCharacter')->andReturnSelf();
        $randomAffixGenerator->shouldReceive('setPaidAmount')->andReturnSelf();
        $randomAffixGenerator->shouldReceive('generateAffix')->with('prefix')->andThrow(new \Exception('affix generation failed'));

        $this->expectException(\Exception::class);

        $this->builder->fetchMythicItem($character);
    }
}
