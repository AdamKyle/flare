<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\GuideQuestService;
use App\Admin\Services\ImageHandlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class GuideQuestServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_only_two_configured_batch_crafted_rows_are_honored_after_cleaning(): void
    {
        $dagger = $this->createItem(['name' => 'Clean Iron Dagger', 'type' => 'dagger', 'can_craft' => true]);
        $helmet = $this->createItem(['name' => 'Clean Iron Helmet', 'type' => 'helmet', 'can_craft' => true]);
        $ring = $this->createItem(['name' => 'Clean Copper Ring', 'type' => 'ring', 'can_craft' => true]);
        $guideQuestService = new GuideQuestService(Mockery::mock(ImageHandlerService::class));

        $params = $guideQuestService->cleanRequest([
            'required_batch_crafting_type' => null,
            'required_batch_crafting_hours' => null,
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'inventory', 'item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'inventory', 'item_id' => $ring->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
            'required_skill_level' => null,
            'required_skill' => null,
            'required_passive_level' => null,
            'required_passive_skill' => null,
            'required_faction_level' => null,
            'required_faction_id' => null,
        ]);

        $this->assertCount(2, $params['required_batch_crafted_items']);
        $this->assertSame($dagger->id, $params['required_batch_crafted_items'][0]['item_id']);
        $this->assertSame($helmet->id, $params['required_batch_crafted_items'][1]['item_id']);
    }
}
