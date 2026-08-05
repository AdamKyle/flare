<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Flare\View\Tables\TableColumn;
use App\Flare\View\Tables\TableQueryBuilder;
use App\Game\Character\CharacterSheet\Transformers\CharacterStatDetailsTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameTopsController extends Controller
{
    public function tops(Request $request): View
    {
        $columns = [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="'.route('game.tops.character.profile', ['character' => $row->id]).'">'.e($row->name).'</a>',
            ),
            new TableColumn(
                label: 'Level',
                field: 'level',
                searchable: true,
                render: fn ($row) => number_format($row->level),
            ),
            new TableColumn(
                label: 'Gold',
                field: 'gold',
                searchable: true,
                render: fn ($row) => number_format($row->gold),
            ),
        ];

        $paginator = TableQueryBuilder::paginate(
            Character::orderBy('level', 'desc'),
            $columns,
            $request
        );

        return view('game.tops.characters', [
            'paginator' => $paginator,
            'columns' => $columns,
        ]);
    }

    public function characterStats(Request $request, Character $character, CharacterStatDetailsTransformer $characterStatDetailsTransformer): View
    {
        $stats = $characterStatDetailsTransformer->transform($character);

        $inventorySetEquipped = InventorySet::where('is_equipped', true)->where('character_id', $character->id)->first();

        if (! is_null($inventorySetEquipped)) {
            $equippedBuilder = SetSlot::where('inventory_set_id', $inventorySetEquipped->id);
        } else {
            $inventory = Inventory::where('character_id', $character->id)->first();
            $equippedBuilder = InventorySlot::where('equipped', true)->where('inventory_id', $inventory->id);
        }

        $equippedColumns = [
            new TableColumn(
                label: 'Item name',
                field: 'item.name',
                searchable: true,
                html: true,
                render: fn ($row) => view('game.items.items-name-for-table')->withValue($row->item)->render(),
            ),
            new TableColumn(
                label: 'Position',
                field: 'position',
                searchable: true,
                render: fn ($row) => ucfirst(str_replace('-', ' ', $row->position)),
            ),
        ];

        $equippedPaginator = TableQueryBuilder::paginate($equippedBuilder, $equippedColumns, $request, 10);

        return view('game.tops.character-info', [
            'character' => $character,
            'attackData' => [
                'attack' => [
                    'weapon_damage' => $stats['weapon_attack'],
                    'ring_damage' => $stats['ring_damage'],
                ],
                'cast' => [
                    'spell_damage' => $stats['spell_damage'],
                    'heal_for' => $stats['healing_amount'],
                ],
            ],
            'equippedPaginator' => $equippedPaginator,
            'equippedColumns' => $equippedColumns,
        ]);
    }

    public function oldCharacterStats(Character $character): RedirectResponse
    {
        return redirect()->route('game.tops.character.profile', ['character' => $character]);
    }

    public function exploration(): View
    {
        return view('game.tops.exploration');
    }

    public function delve(): View
    {
        return view('game.tops.delve');
    }

    public function factionLoyalty(): View
    {
        return view('game.tops.faction-loyalty');
    }

    public function kingdoms(): View
    {
        return view('game.tops.kingdoms');
    }
}
