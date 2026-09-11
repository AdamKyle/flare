<?php

namespace Tests\Feature\Admin\Items;

use App\Admin\Items\Exports\Sheets\ItemsSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemExportControllerTest extends TestCase
{
    use CreateItem, CreateRole, CreateUser, RefreshDatabase;

    public function test_export_without_a_profile_fails_validation_instead_of_defaulting_to_all(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items/export');

        $response->assertInvalid(['profile']);
    }

    public function test_export_with_an_invalid_profile_fails_validation(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items/export', ['profile' => 'all']);

        $response->assertInvalid(['profile']);
    }

    public function test_export_with_a_valid_profile_downloads_the_items_workbook(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items/export', ['profile' => 'weapons']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('items.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_items_sheet_excludes_randomly_generated_scrolls_and_includes_normal_catalog_items(): void
    {
        $catalogItem = $this->createItem(['type' => 'weapon', 'market_sellable' => true]);
        $scrollItem = $this->createGemXpScrollItem(0.10);

        $items = (new ItemsSheet)->view()->getData()['items'];

        $this->assertTrue($items->contains('id', $catalogItem->id));
        $this->assertFalse($items->contains('id', $scrollItem->id));
    }
}
