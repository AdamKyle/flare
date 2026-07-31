<?php

namespace Tests\Feature\Admin\Users;

use App\Flare\Models\InventorySlot;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\View\Livewire\Admin\Users\GiveToPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GiveToPlayerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateKingdom, CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_user_show_page_renders_give_to_player_component(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->actingAs($admin)
            ->visit(route('users.user', ['user' => $character->user]))
            ->see('Give To Player')
            ->see('Grant Type');
    }

    public function test_admin_can_search_and_give_item(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['name' => 'Admin Granted Sword']);

        Livewire::test(GiveToPlayer::class, ['character' => $character])
            ->set('itemSearch', 'Granted')
            ->assertSee('Admin Granted Sword')
            ->call('selectItem', $item->id)
            ->call('give')
            ->assertSet('itemSearch', '')
            ->assertSet('selectedItemId', null)
            ->assertSee('Admin Granted Sword was given to '.$character->name.'.');

        $this->assertSame(1, InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $item->id)->count());
    }

    public function test_admin_can_give_currencies_with_caps(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        Livewire::test(GiveToPlayer::class, ['character' => $character])
            ->set('grantType', 'gold')
            ->set('currency', 'gold')
            ->set('amount', MaxCurrenciesValue::MAX_GOLD)
            ->call('give')
            ->assertSee('Gold was given to '.$character->name.'.');

        $this->assertSame(MaxCurrenciesValue::MAX_GOLD, $character->refresh()->gold);

        foreach (['gold_dust', 'shards', 'copper_coins'] as $currency) {
            Livewire::test(GiveToPlayer::class, ['character' => $character->refresh()])
                ->set('grantType', $currency)
                ->set('currency', $currency)
                ->set('amount', 50)
                ->call('give')
                ->assertSee(ucfirst(str_replace('_', ' ', $currency)).' was given to '.$character->name.'.');

            $this->assertSame(50, $character->refresh()->{$currency});
        }
    }

    public function test_admin_can_give_gold_bars_to_all_kingdoms_with_caps(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $firstKingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'gold_bars' => 995,
        ]);
        $secondMap = $this->createGameMap(['name' => 'Hell', 'default' => false]);
        $secondKingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $secondMap->id,
            'gold_bars' => 10,
        ]);

        Livewire::test(GiveToPlayer::class, ['character' => $character])
            ->set('grantType', 'gold_bars')
            ->set('amount', 10)
            ->call('give')
            ->assertSee('Gold bars were given to all kingdoms owned by '.$character->name.'.');

        $this->assertSame(1000, $firstKingdom->refresh()->gold_bars);
        $this->assertSame(20, $secondKingdom->refresh()->gold_bars);
    }

    public function test_non_admin_cannot_access_admin_user_show_page(): void
    {
        $user = $this->createUser();
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $response = $this->actingAs($user)->call('GET', route('users.user', ['user' => $character->user]));

        $this->assertSame(302, $response->getStatusCode());
    }
}
