<?php

use Illuminate\Support\Facades\Cache;
use Tests\Traits\CreateItem;

uses(CreateItem::class);

test('fetch craftable items returns craftable items ordered by cost', function () {
    Cache::forget('crafting-table-data');
    $cheap = $this->createItem(['name' => 'Cheap Sword', 'type' => 'sword', 'can_craft' => true, 'cost' => 10]);
    $expensive = $this->createItem(['name' => 'Expensive Sword', 'type' => 'sword', 'can_craft' => true, 'cost' => 500]);
    $this->createItem(['name' => 'Not Craftable', 'type' => 'sword', 'can_craft' => false, 'cost' => 5]);
    $this->createItem(['name' => 'Quest Item', 'type' => 'quest', 'can_craft' => true, 'cost' => 5]);

    $response = $this->get('/api/items-list');

    $response->assertOk();
    $response->assertJsonCount(2, 'items.data');
    expect($response->json('items.data.0.id'))->toBe($cheap->id);
    expect($response->json('items.data.1.id'))->toBe($expensive->id);
});

test('fetch craftable items caches the result when there is no filter or search', function () {
    Cache::forget('crafting-table-data');
    $this->createItem(['name' => 'Cheap Sword', 'type' => 'sword', 'can_craft' => true, 'cost' => 10]);

    $this->get('/api/items-list')->assertOk();

    expect(Cache::get('crafting-table-data'))->not->toBeNull();
});

test('fetch craftable items returns cached data on a subsequent request without filters', function () {
    Cache::put('crafting-table-data', [['id' => 999, 'name' => 'Cached Item']]);

    $response = $this->get('/api/items-list');

    $response->assertOk();
    $response->assertExactJson(['items' => [['id' => 999, 'name' => 'Cached Item']]]);
});

test('fetch craftable items filters by type and bypasses the cache', function () {
    Cache::put('crafting-table-data', [['id' => 999, 'name' => 'Cached Item']]);
    $sword = $this->createItem(['name' => 'Craftable Sword', 'type' => 'sword', 'can_craft' => true]);
    $this->createItem(['name' => 'Craftable Mace', 'type' => 'mace', 'can_craft' => true]);

    $response = $this->get('/api/items-list?filter=sword');

    $response->assertOk();
    $response->assertJsonCount(1, 'items.data');
    expect($response->json('items.data.0.id'))->toBe($sword->id);
});

test('fetch craftable items searches by name and bypasses the cache', function () {
    Cache::put('crafting-table-data', [['id' => 999, 'name' => 'Cached Item']]);
    $match = $this->createItem(['name' => 'Ironclad Helm', 'type' => 'helmet', 'can_craft' => true]);
    $this->createItem(['name' => 'Steel Boots', 'type' => 'boots', 'can_craft' => true]);

    $response = $this->get('/api/items-list?search_text=ironclad');

    $response->assertOk();
    $response->assertJsonCount(1, 'items.data');
    expect($response->json('items.data.0.id'))->toBe($match->id);
});

test('fetch specific set returns items matching the specialty type', function () {
    $matching = $this->createItem(['name' => 'Sun Blade', 'type' => 'sword', 'specialty_type' => 'sun-forge']);
    $this->createItem(['name' => 'Regular Blade', 'type' => 'sword', 'specialty_type' => null]);

    $response = $this->get('/api/items-list-for-type?specialty_type=sun-forge');

    $response->assertOk();
    $response->assertJsonCount(1, 'items.data');
    expect($response->json('items.data.0.id'))->toBe($matching->id);
});
