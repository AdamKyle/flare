<?php

namespace Tests\Unit\Flare\Items\Builders;

use App\Flare\Items\Builders\RandomItemDropBuilder;
use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class RandomItemDropBuilderTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

    private ?RandomItemDropBuilder $builder = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->app->make(RandomItemDropBuilder::class);
    }

    protected function tearDown(): void
    {
        $this->builder = null;

        parent::tearDown();
    }

    public function test_generate_item_creates_item_with_prefix_only(): void
    {
        $this->createItemAffix(['type' => 'prefix', 'name' => 'Sharp', 'skill_level_required' => 1]);
        $this->createItemAffix(['type' => 'suffix', 'name' => 'Of Power', 'skill_level_required' => 1]);

        $allowedBase = $this->createItem([
            'type' => 'ring',
            'name' => 'Plain Ring',
            'skill_level_required' => 1,
        ]);

        $builder = Mockery::mock(RandomItemDropBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $builder->shouldReceive('rollLevel')->andReturn(10);
        $builder->shouldReceive('rollPercent')->andReturn(10);

        $result = $builder->generateItem(10);

        $this->assertInstanceOf(Item::class, $result);
        $this->assertSame('ring', $result->type);
        $this->assertNotSame($allowedBase->id, $result->id);
        $this->assertNotNull($result->item_prefix_id);
        $this->assertNull($result->item_suffix_id);
    }

    public function test_generate_item_creates_item_with_prefix_and_suffix(): void
    {
        $this->createItemAffix(['type' => 'prefix', 'name' => 'Bright', 'skill_level_required' => 1]);
        $this->createItemAffix(['type' => 'suffix', 'name' => 'Of Stars', 'skill_level_required' => 1]);

        $allowedBase = $this->createItem([
            'type' => 'ring',
            'name' => 'Plain Ring',
            'skill_level_required' => 1,
        ]);

        $builder = Mockery::mock(RandomItemDropBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $builder->shouldReceive('rollLevel')->andReturn(10);
        $builder->shouldReceive('rollPercent')->andReturn(100);

        $result = $builder->generateItem(10);

        $this->assertInstanceOf(Item::class, $result);
        $this->assertSame('ring', $result->type);
        $this->assertNotSame($allowedBase->id, $result->id);
        $this->assertNotNull($result->item_prefix_id);
        $this->assertNotNull($result->item_suffix_id);
    }

    public function test_get_item_excludes_disallowed_types_indirectly_through_result_type(): void
    {
        $this->createItem(['type' => 'quest', 'name' => 'Questy', 'skill_level_required' => 1]);
        $this->createItem(['type' => 'alchemy', 'name' => 'Alchy', 'skill_level_required' => 1]);
        $this->createItem(['type' => 'trinket', 'name' => 'Trinky', 'skill_level_required' => 1]);
        $this->createItem(['type' => 'artifact', 'name' => 'Arty', 'skill_level_required' => 1]);

        $this->createItemAffix(['type' => 'prefix', 'name' => 'Calm', 'skill_level_required' => 1]);
        $this->createItemAffix(['type' => 'suffix', 'name' => 'Of Oaks', 'skill_level_required' => 1]);

        $allowedBase = $this->createItem([
            'type' => 'ring',
            'name' => 'Allowed',
            'skill_level_required' => 1,
        ]);

        $builder = Mockery::mock(RandomItemDropBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $builder->shouldReceive('rollLevel')->andReturn(10);
        $builder->shouldReceive('rollPercent')->andReturn(5);

        $result = $builder->generateItem(10);

        $this->assertInstanceOf(Item::class, $result);
        $this->assertSame('ring', $result->type);
        $this->assertNotSame($allowedBase->id, $result->id);
        $this->assertNotNull($result->item_prefix_id);
        $this->assertNull($result->item_suffix_id);
        $this->assertNotContains($result->type, ['quest', 'alchemy', 'trinket', 'artifact']);
    }
}
