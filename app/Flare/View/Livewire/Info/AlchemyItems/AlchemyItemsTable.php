<?php

namespace App\Flare\View\Livewire\Info\AlchemyItems;

use App\Flare\Items\Values\AlchemyItemType;
use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AlchemyItemsTable extends DataTableComponent
{
    /**
     * Configures the table's primary key.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    /**
     * Builds the query for retrieving alchemy items.
     */
    public function builder(): Builder
    {
        return Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('type', 'alchemy')
            ->whereNull('gold_bars_cost');
    }

    /**
     * Defines filters for the table.
     */
    public function filters(): array
    {
        return [
            SelectFilter::make('Types')
                ->options($this->buildOptions())
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('alchemy_type', $value);

                    return $builder;
                }),
        ];
    }

    /**
     * Defines the columns displayed in the table.
     */
    public function columns(): array
    {
        return [
            Column::make('Name')
                ->searchable()
                ->format(function ($value, $row) {
                    $itemId = Item::where('name', $value)->first()->id;

                    return '<a href="/items/'.$itemId.'">'.$row->name.'</a>';
                })
                ->html(),
            Column::make('Gold Dust Cost')
                ->sortable()
                ->format(function ($value) {
                    return number_format($value);
                }),
            Column::make('Shards Cost')
                ->sortable()
                ->format(function ($value) {
                    return number_format($value);
                }),
            Column::make('Min Crafting Lv.', 'skill_level_required')
                ->sortable(),
            Column::make('Trivial Crafting Lv.', 'skill_level_trivial')
                ->sortable(),
        ];
    }

    /**
     * Builds an array of alchemy item type options for the filter.
     */
    private function buildOptions(): array
    {
        return [
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
