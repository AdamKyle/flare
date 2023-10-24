<?php

namespace App\Flare\View\Livewire\Info\AlchemyItems;

use App\Flare\AlchemyItemGenerator\Values\AlchemyItemType;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AlchemyItemsTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('type', 'alchemy')
            ->whereNull('gold_bars_cost');
    }

    protected function buildOptions(): array {
        return [
            AlchemyItemType::INCREASE_STATS => 'Increases Stats',
            AlchemyItemType::INCREASE_SKILL_TYPE => 'Increases Training Skills',
            AlchemyItemType::INCREASE_DAMAGE => 'Increases Damage',
            AlchemyItemType::INCREASE_ARMOUR => 'Increases Armour',
            AlchemyItemType::INCREASE_HEALING => 'Increases Healing',
            AlchemyItemType::INCREASE_ALCHEMY_SKILL => 'Increases Alchemy Skill',
            AlchemyItemType::DAMAGES_KINGDOMS => 'Damages Kingdoms',
            AlchemyItemType::HOLY_OILS => 'Holy Oils',
        ];
    }

    public function filters(): array {
        return [
            SelectFilter::make('Types')
                ->options($this->buildOptions())
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('alchemy_type', $value);

                    return $builder;
                }),
        ];
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                return '<a href="/items/' . $itemId . '" >' . $row->name . '</a>';
            })->html(),
            Column::make('Gold Dust Cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Shards Cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Min Crafting Lv.', 'skill_level_required')->sortable(),
            Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable(),
        ];
    }
}
