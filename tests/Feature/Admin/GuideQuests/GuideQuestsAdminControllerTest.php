<?php

namespace Tests\Feature\Admin\GuideQuests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GuideQuestsAdminControllerTest extends TestCase
{
    use CreateGuideQuest, CreateItem, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_renders_the_modern_admin_shell(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->get('/admin/guide-quests')
            ->assertOk()
            ->assertSee('id="guide-quests-admin-app"', false);
    }

    public function test_list_api_is_admin_only(): void
    {
        $this->getJson('/api/admin/guide-quests')->assertUnauthorized();

        $this->actingAs($this->createUser())
            ->getJson('/api/admin/guide-quests')
            ->assertForbidden();
    }

    public function test_list_api_searches_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGuideQuest(['name' => 'Welcome to Flare']);
        $this->createGuideQuest(['name' => 'Kingdom Tutorial']);

        $response = $this->actingAs($admin)->getJson('/api/admin/guide-quests?search_text=Kingdom');

        $this->assertSame(['Kingdom Tutorial'], array_column($response->json('data'), 'name'));
    }

    public function test_list_api_sorts_by_required_level(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGuideQuest(['name' => 'Low', 'required_level' => 2]);
        $this->createGuideQuest(['name' => 'High', 'required_level' => 20]);

        $response = $this->actingAs($admin)->getJson('/api/admin/guide-quests?sort_key=required_level&sort_direction=desc');

        $this->assertSame('High', $response->json('data.0.name'));
    }

    public function test_detail_api_returns_the_full_guide_quest(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $guideQuest = $this->createGuideQuest(['name' => 'Detailed Guide', 'unlock_at_level' => 7]);

        $this->actingAs($admin)
            ->getJson('/api/admin/guide-quests/'.$guideQuest->id)
            ->assertOk()
            ->assertJson(['id' => $guideQuest->id, 'name' => 'Detailed Guide', 'unlock_at_level' => 7]);
    }

    public function test_create_editor_route_renders_the_react_mount(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->get('/admin/guide-quests/create')
            ->assertOk()
            ->assertSee('id="guide-quest-editor"', false);
    }

    public function test_edit_editor_route_renders_the_react_mount(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $guideQuest = $this->createGuideQuest();

        $this->actingAs($admin)
            ->get('/admin/guide-quests/edit/'.$guideQuest->id)
            ->assertOk()
            ->assertSee('id="guide-quest-editor"', false);
    }

    public function test_manage_blade_compiles_without_a_parse_error(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->get('/admin/guide-quests/create');

        $this->assertSame(200, $response->getStatusCode());
        $response->assertSee('data-guide-quest-id="0"', false);
    }

    public function test_full_guide_quest_content_payload_is_accepted(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $guideQuest = $this->createGuideQuest();

        $this->actingAs($admin)
            ->postJson('/api/admin/store-guide-quest', [
                'guide_quest_id' => $guideQuest->id,
                'has_image' => false,
                'content' => [
                    'name' => 'Updated Guide Quest',
                    'instructions' => 'Inspect the complete form.',
                    'intro_text' => [],
                    'desktop_instructions' => [],
                    'mobile_instructions' => [],
                    'required_level' => 10,
                    'required_training_xp_sacrifice' => 0.25,
                    'required_batch_crafted_items' => [],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('guide_quest.name', 'Updated Guide Quest');
    }

    public function test_unexpected_guide_quest_content_key_is_rejected(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->postJson('/api/admin/store-guide-quest', [
                'guide_quest_id' => 0,
                'has_image' => false,
                'content' => ['name' => 'Guide Quest', 'id' => 123],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    public function test_guide_quest_name_is_required(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->postJson('/api/admin/store-guide-quest', [
                'guide_quest_id' => 0,
                'has_image' => false,
                'content' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content.name')
            ->assertJsonFragment(['Guide Quest Name is required.']);
    }

    public function test_training_xp_sacrifice_must_be_within_ratio_range(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->postJson('/api/admin/store-guide-quest', [
                'guide_quest_id' => 0,
                'has_image' => false,
                'content' => [
                    'name' => 'Guide Quest',
                    'required_training_xp_sacrifice' => 2,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content.required_training_xp_sacrifice')
            ->assertJsonFragment(['Training XP Sacrifice cannot be above 1.']);
    }

    public function test_batch_crafted_item_amount_must_be_at_least_one(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem();

        $this->actingAs($admin)
            ->postJson('/api/admin/store-guide-quest', [
                'guide_quest_id' => 0,
                'has_image' => false,
                'content' => [
                    'name' => 'Guide Quest',
                    'required_batch_crafted_items' => [[
                        'source' => 'inventory',
                        'item_id' => $item->id,
                        'amount' => 0,
                        'must_be_enchanted' => false,
                    ]],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content.required_batch_crafted_items.0.amount')
            ->assertJsonFragment(['Required crafted item amount must be at least 1.']);
    }
}
