<?php

namespace App\Flare\View\Livewire\Admin\Items;

use App\Flare\Models\Item;
use App\Flare\View\Livewire\Admin\Items\Values\ItemTableSelectOptions;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ItemsTable extends DataTableComponent
{
    public $isShop = false;

    public $type = null;

    public $locationId = null;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        $item = $this->buildItemQuery();

        return $item->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id');
    }

    protected function buildItemQuery(): Builder
    {
        $query = Item::query();

        if (! is_null($this->type) && ! is_null($this->locationId)) {
            $query = Item::where('type', $this->type)->where('drop_location_id', $this->locationId);
        }

        if ($this->isShop) {
            $query = $query->where('cost', '<=', 2000000000)
                ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
                ->whereNull('item_suffix_id')
                ->whereNull('item_prefix_id')
                ->whereNull('specialty_type');
        }

        return $query;
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Types')
                ->options($this->buildOptions())
                ->filter(function (Builder $builder, string $value) {
                    $builder = $builder->where('type', $value);

                    if (in_array($value, ['quest', 'alchemy', 'trinket'])) {

                        if ($value === 'alchemy') {
                            if (! is_null(auth()->user())) {
                                if (auth()->user()->hasRole('Admin')) {
                                    $builder->where('gold_bars_cost', '>', 0);
                                }
                            } else {
                                return $builder->whereNotNull('skill_level_required');
                            }
                        }

                        return $builder;
                    }

                    $builder = $builder->whereNull('item_suffix_id')
                        ->whereNull('item_prefix_id');

                    if ($value === 'artifact') {
                        $builder = $builder->doesntHave('itemSkillProgressions');
                    }

                    if (! is_null(auth()->user())) {
                        if (! auth()->user()->hasRole('Admin')) {
                            $builder = $builder->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
                                ->where('cost', '<=', 2000000000);
                        }
                    }

                    if ($this->isShop) {
                        $builder->whereNull('specialty_type');
                    }

                    return $builder;
                }),
        ];
    }

    protected function buildOptions(): array
    {
        $options = ItemTableSelectOptions::getLabels();

        if (! is_null(auth()->user())) {
            if (auth()->user()->hasRole('Admin')) {

                $options = [
                    ...$options,
                    ...[
                        ItemTableSelectOptions::TRINKET->value => 'Trinkets',
                        ItemTableSelectOptions::QUEST->value => 'Quest items',
                        ItemTableSelectOptions::ALCHEMY->value => 'Alchemy items',
                        ItemTableSelectOptions::ARTIFACT->value => 'Artifacts',
                    ],
                ];
            }
        }

        return $options;
    }

    public function columns(): array
    {
        $columns = [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                if (! is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/items/'.$itemId.'">'.$row->name.'</a>';
                    }
                }

                return '<a href="/items/'.$itemId.'" >'.$row->name.'</a>';
            })->html(),
            Column::make('Type')->searchable()->format(function ($value) {
                return ucfirst(str_replace('-', ' ', $value));
            }),

            Column::make('Damage', 'base_damage')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('AC', 'base_ac')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Healing', 'base_healing')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Cost')->sortable()->format(function ($value) {
                $cost = $value;

                if (auth()->user()) {
                    if (! is_null(auth()->user()->character)) {
                        $character = auth()->user()->character;

                        if ($character->classType()->isMerchant()) {
                            $cost = floor($cost - $cost * 0.25);
                        }
                    }
                }

                return number_format($cost);
            }),
        ];

        if (! is_null(auth()->user())) {
            if (! auth()->user()->hasRole('Admin') && $this->isShop) {
                $columns[] = Column::make('Actions')->label(
                    fn ($row, Column $column) => view('admin.items.table-components.shop-actions-section', [
                        'character' => auth()->user()->character,
                    ])->withRow($row)
                );
            } else {
                $columns[] = Column::make('Min Crafting Lv.', 'skill_level_required')->sortable();
                $columns[] = Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable();
            }
        } else {
            $columns[] = Column::make('Min Crafting Lv.', 'skill_level_required')->sortable();
            $columns[] = Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable();
        }

        return $columns;
    }
}
