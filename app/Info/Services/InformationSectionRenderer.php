<?php

namespace App\Info\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Tables\TableColumn;
use App\Flare\Tables\TableQueryBuilder;
use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Maps\Values\LocationType;
use App\Info\Tables\Definitions\LocationGemsTableDefinition;
use App\Info\Tables\Definitions\MapGemsTableDefinition;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InformationSectionRenderer
{
    public function supports(string $alias): bool
    {
        return array_key_exists($alias, $this->resolvers());
    }

    public function render(string $alias, Request $request): ?View
    {
        $resolver = $this->resolvers()[$alias] ?? null;

        if (is_null($resolver)) {
            return null;
        }

        $columns = $resolver['columns']();
        $builder = $resolver['builder']($request);

        return view('components.core.tables.data-table', [
            'paginator' => TableQueryBuilder::paginate($builder, $columns, $request),
            'columns' => $columns,
            'searchable' => true,
            'filters' => $resolver['filters'] ?? [],
            'emptyMessage' => 'No records found.',
        ]);
    }

    /**
     * @return array<string, array{builder: \Closure(Request): Builder, columns: \Closure(): TableColumn[], filters?: array}>
     */
    private function resolvers(): array
    {
        return [
            'info.items.ancestral-items' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->where('type', 'artifact')
                    ->whereDoesntHave('itemSkillProgressions'),
                'columns' => fn () => self::specialtyItemColumns(false),
            ],
            'info.items.corrupted-ice' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::CORRUPTED_ICE->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.delusional-silver' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::DELUSIONAL_SILVER->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.faithless-plate' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::FAITHLESS_PLATE->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.hell-forged' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::HELL_FORGED->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.labyrinth-cloth' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::LABYRINTH_CLOTH->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.pirate-lord-leather' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::PIRATE_LORD_LEATHER->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.purgatory-chains' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.twisted-earth' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->where('specialty_type', ItemSpecialtyType::TWISTED_EARTH->value),
                'columns' => fn () => self::specialtyItemColumns(true),
            ],
            'info.items.craftable-items-table' => [
                'builder' => function (Request $request) {
                    $builder = Item::whereNull('item_prefix_id')
                        ->whereNull('item_suffix_id')
                        ->where('can_craft', true)
                        ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact']);

                    if ($type = $request->query('type')) {
                        $builder->where('type', $type);
                    }

                    return $builder;
                },
                'columns' => fn () => self::craftableItemsColumns(),
                'filters' => [
                    [
                        'field' => 'type',
                        'label' => 'Types',
                        'options' => self::craftableItemTypeOptions(),
                    ],
                ],
            ],
            'info.items.craftable-trinkets' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->where('can_craft', true)
                    ->where('type', 'trinket'),
                'columns' => fn () => [
                    new TableColumn(
                        label: 'Name',
                        field: 'name',
                        searchable: true,
                        html: true,
                        render: fn ($row) => '<a href="/items/'.$row->id.'">'.e($row->name).'</a>',
                    ),
                    new TableColumn('Ambush Chance', 'ambush_chance', sortable: true, render: fn ($row) => ($row->ambush_chance * 100).'%'),
                    new TableColumn('Ambush Resistance', 'ambush_resistance', sortable: true, render: fn ($row) => ($row->ambush_resistance * 100).'%'),
                    new TableColumn('Counter Chance', 'counter_chance', sortable: true, render: fn ($row) => ($row->counter_chance * 100).'%'),
                    new TableColumn('Counter Resistance', 'counter_resistance', sortable: true, render: fn ($row) => ($row->counter_resistance * 100).'%'),
                    new TableColumn('Gold Dust Cost', 'gold_dust_cost', sortable: true, render: fn ($row) => number_format($row->gold_dust_cost)),
                    new TableColumn('Copper Coin Cost', 'copper_coin_cost', sortable: true, render: fn ($row) => number_format($row->copper_coin_cost)),
                    new TableColumn('Min Crafting Lv.', 'skill_level_required', sortable: true),
                    new TableColumn('Trivial Crafting Lv.', 'skill_level_trivial', sortable: true),
                ],
            ],
            'info.quest-items.crafting-books-table' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->where('type', 'quest')
                    ->where('skill_name', 'like', '%Crafting%'),
                'columns' => fn () => self::questItemColumns(),
            ],
            'info.quest-items.quest-items-table' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->where('type', 'quest'),
                'columns' => fn () => self::questItemColumns(),
            ],
            'info.locations.regular-locations' => [
                'builder' => fn () => Location::whereNull('type'),
                'columns' => fn () => self::locationColumns(),
            ],
            'info.locations.special-locations' => [
                'builder' => fn () => Location::whereNotNull('type'),
                'columns' => fn () => self::locationColumns(),
            ],
            'info.locations.weekly-fight-locations' => [
                'builder' => fn () => Location::whereIn('type', [
                    LocationType::LORDS_STRONG_HOLD->value,
                    LocationType::BROKEN_ANVIL->value,
                    LocationType::TWISTED_MAIDENS_DUNGEONS->value,
                    LocationType::ALCHEMY_CHURCH->value,
                ]),
                'columns' => fn () => self::locationColumns(withEventStar: true),
            ],
            'info.skills.class-skills' => [
                'builder' => fn () => GameSkill::whereNotNull('game_class_id'),
                'columns' => fn () => self::classSkillColumns(),
            ],
            'info.alchemy-items.alchemy-items-table' => [
                'builder' => function (Request $request) {
                    $builder = Item::whereNull('item_prefix_id')
                        ->whereNull('item_suffix_id')
                        ->where('type', 'alchemy')
                        ->whereNull('gold_bars_cost');

                    if ($type = $request->query('alchemy_type')) {
                        $builder->where('alchemy_type', $type);
                    }

                    return $builder;
                },
                'columns' => fn () => self::alchemyItemColumns(),
                'filters' => [
                    [
                        'field' => 'alchemy_type',
                        'label' => 'Types',
                        'options' => self::alchemyItemTypeOptions(),
                    ],
                ],
            ],
            'info.alchemy-items.alchemy-holy-items-table' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->where('type', 'alchemy')
                    ->where('can_use_on_other_items', true)
                    ->orderBy('skill_level_required', 'asc'),
                'columns' => fn () => self::alchemyItemColumns(),
            ],
            'info.alchemy-items.alchemy-kingdom-items-table' => [
                'builder' => fn () => Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->where('type', 'alchemy')
                    ->where('damages_kingdoms', true)
                    ->where('can_use_on_other_items', false)
                    ->orderBy('skill_level_required', 'asc'),
                'columns' => fn () => self::alchemyItemColumns(),
            ],
            'info.map-gems' => [
                'builder' => fn () => MapGemsTableDefinition::builder(),
                'columns' => fn () => MapGemsTableDefinition::columns(),
            ],
            'info.location-gems' => [
                'builder' => fn () => LocationGemsTableDefinition::builder(),
                'columns' => fn () => LocationGemsTableDefinition::columns(),
            ],
        ];
    }

    /**
     * @return TableColumn[]
     */
    private static function specialtyItemColumns(bool $includeCosts): array
    {
        $columns = [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="/items/'.$row->id.'">'.e($row->name).'</a>',
            ),
            new TableColumn(
                label: 'Type',
                field: 'type',
                searchable: true,
                render: fn ($row) => ucfirst(str_replace('-', ' ', $row->type)),
            ),
            new TableColumn('Damage', 'base_damage', sortable: true, render: fn ($row) => number_format($row->base_damage)),
            new TableColumn('AC', 'base_ac', sortable: true, render: fn ($row) => number_format($row->base_ac)),
            new TableColumn('Healing', 'base_healing', sortable: true, render: fn ($row) => number_format($row->base_healing)),
        ];

        if ($includeCosts) {
            $columns[] = new TableColumn('Gold Cost', 'cost', sortable: true, render: fn ($row) => number_format($row->cost));
            $columns[] = new TableColumn('Gold Dust Cost', 'gold_dust_cost', sortable: true, render: fn ($row) => number_format($row->gold_dust_cost));
            $columns[] = new TableColumn('Shard Cost', 'shards_cost', sortable: true, render: fn ($row) => number_format($row->shards_cost));
            $columns[] = new TableColumn('Copper Coin Cost', 'copper_coin_cost', sortable: true, render: fn ($row) => number_format($row->copper_coin_cost));
        }

        return $columns;
    }

    /**
     * @return TableColumn[]
     */
    private static function craftableItemsColumns(): array
    {
        return [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="/items/'.$row->id.'">'.e($row->name).'</a>',
            ),
            new TableColumn(
                label: 'Type',
                field: 'type',
                searchable: true,
                render: fn ($row) => ucfirst(str_replace('-', ' ', $row->type)),
            ),
            new TableColumn('Damage', 'base_damage', sortable: true, render: fn ($row) => number_format($row->base_damage)),
            new TableColumn('AC', 'base_ac', sortable: true, render: fn ($row) => number_format($row->base_ac)),
            new TableColumn('Healing', 'base_healing', sortable: true, render: fn ($row) => number_format($row->base_healing)),
            new TableColumn('Cost', 'cost', sortable: true, render: fn ($row) => number_format($row->cost)),
            new TableColumn('Min Crafting Lv.', 'skill_level_required', sortable: true),
            new TableColumn('Trivial Crafting Lv.', 'skill_level_trivial', sortable: true),
        ];
    }

    private static function craftableItemTypeOptions(): array
    {
        return array_merge(
            ['' => 'Please Select'],
            ItemType::getValidWeaponsAsOptions(),
            [
                'body' => 'Body',
                'helmet' => 'Helmets',
                'shield' => 'Shields',
                'sleeves' => 'Sleeves',
                'gloves' => 'Gloves',
                'leggings' => 'Leggings',
                'feet' => 'Feet',
            ]
        );
    }

    /**
     * @return TableColumn[]
     */
    private static function questItemColumns(): array
    {
        return [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="/items/'.$row->id.'">'.e($row->name).'</a>',
            ),
            new TableColumn(
                label: 'Type',
                field: 'type',
                searchable: true,
                render: fn ($row) => ucfirst(str_replace('-', ' ', $row->type)),
            ),
            new TableColumn('Description', 'description'),
        ];
    }

    /**
     * @return TableColumn[]
     */
    private static function locationColumns(bool $withEventStar = false): array
    {
        return [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="/information/locations/'.$row->id.'">'.e($row->name).'</a>',
            ),
            new TableColumn(
                label: 'Game Map',
                field: 'game_map_id',
                searchable: true,
                sortable: true,
                html: true,
                render: function ($row) use ($withEventStar) {
                    $gameMap = GameMap::find($row->game_map_id);

                    if (! $withEventStar) {
                        return e($gameMap->name);
                    }

                    return '<span>'.e($gameMap->name).($gameMap->only_during_event_type ? ' <i class="fas fa-star text-yellow-700 dark:text-yellow-500"></i> ' : '').'</span>';
                },
            ),
            new TableColumn('X Coordinate', 'x', sortable: true),
            new TableColumn('Y Coordinate', 'y', sortable: true),
        ];
    }

    /**
     * @return TableColumn[]
     */
    private static function classSkillColumns(): array
    {
        return [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: function ($row) {
                    if (! is_null(auth()->user()) && auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/skill/'.$row->id.'">'.e($row->name).'</a>';
                    }

                    return '<a href="/information/skill/'.$row->id.'">'.e($row->name).'</a>';
                },
            ),
            new TableColumn(
                label: 'Trainable?',
                field: 'can_train',
                sortable: true,
                render: fn ($row) => $row->can_train ? 'Yes' : 'No',
            ),
            new TableColumn(
                label: 'Game Class',
                field: 'game_class_id',
                sortable: true,
                html: true,
                render: function ($row) {
                    if (is_null($row->game_class_id)) {
                        return 'N/A';
                    }

                    if (! is_null(auth()->user()) && auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/classes/'.$row->game_class_id.'">'.e($row->gameClass->name).'</a>';
                    }

                    return '<a href="/information/class/'.$row->game_class_id.'">'.e($row->gameClass->name).'</a>';
                },
            ),
            new TableColumn(
                label: 'Is Locked?',
                field: 'is_locked',
                sortable: true,
                render: fn ($row) => $row->is_locked ? 'Yes' : 'No',
            ),
            new TableColumn(
                label: 'Skill Type',
                field: 'type',
                sortable: true,
                render: fn ($row) => is_null($row->type) ? 'N/A' : $row->skillType()->getNamedValue(),
            ),
        ];
    }

    /**
     * @return TableColumn[]
     */
    private static function alchemyItemColumns(): array
    {
        return [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: fn ($row) => '<a href="/items/'.$row->id.'">'.e($row->name).'</a>',
            ),
            new TableColumn('Gold Dust Cost', 'gold_dust_cost', sortable: true, render: fn ($row) => number_format($row->gold_dust_cost)),
            new TableColumn('Shards Cost', 'shards_cost', sortable: true, render: fn ($row) => number_format($row->shards_cost)),
            new TableColumn('Min Crafting Lv.', 'skill_level_required', sortable: true),
            new TableColumn('Trivial Crafting Lv.', 'skill_level_trivial', sortable: true),
        ];
    }

    private static function alchemyItemTypeOptions(): array
    {
        return [
            '' => 'Please Select',
            AlchemyItemType::INCREASE_STATS->value => 'Increases Stats',
            AlchemyItemType::INCREASE_SKILL_TYPE->value => 'Increases Training Skills',
            AlchemyItemType::INCREASE_DAMAGE->value => 'Increases Damage',
            AlchemyItemType::INCREASE_ARMOUR->value => 'Increases Armour',
            AlchemyItemType::INCREASE_HEALING->value => 'Increases Healing',
            AlchemyItemType::INCREASE_ALCHEMY_SKILL->value => 'Increases Alchemy Skill',
            AlchemyItemType::DAMAGES_KINGDOMS->value => 'Damages Kingdoms',
            AlchemyItemType::HOLY_OILS->value => 'Holy Oils',
        ];
    }
}
