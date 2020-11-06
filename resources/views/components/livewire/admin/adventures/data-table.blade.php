<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$adventures">
                    <x-data-tables.header>
                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('name')" 
                            header-text="Name" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('levels')" 
                            header-text="Total Levels" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="levels"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('time_per_level')" 
                            header-text="Tme Per Level" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="time_per_level"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('gold_rush_chance')" 
                            header-text="Gold rush chance" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="gold_rush_chance"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('item_find_chance')" 
                            header-text="Item find chance" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="item_find_chance"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('skill_exp_bonus')" 
                            header-text="Skill XP Bonus" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="skill_exp_bonus"
                        />
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($adventures as $adventure)
                            <tr>
                                <td>
                                    @guest
                                        <a href="{{route('info.page.adventure', [
                                            'adventure' => $adventure->id
                                        ])}}">{{$adventure->name}}</a>
                                    @else
                                        <a href="{{route('adventures.adventure', [
                                            'adventure' => $adventure->id
                                        ])}}">{{$adventure->name}}</a>
                                    @endif
                                </td>
                                <td>{{$adventure->levels}}</td>
                                <td>{{$adventure->time_per_level}} Minutes</td>
                                <td>{{$adventure->gold_rush_chance * 100}}%</td>
                                <td>{{$adventure->item_find_chance * 100}}%</td>
                                <td>{{$adventure->skill_exp_bonus * 100}}%</td>
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="6" />
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
