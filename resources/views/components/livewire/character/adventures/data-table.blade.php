<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card additionalClasses="overflow-table">
            <div class="row pb-2">
                <x-data-tables.per-page wire:model="perPage" />
                <x-data-tables.search wire:model="search" />
            </div>
            <x-data-tables.table :collection="$logs">
                <x-data-tables.header>
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('adventure.name')"
                        header-text="Name"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="adventure.name"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('complete')"
                        header-text="Is Complete"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="complete"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('last_completed_level')"
                        header-text="Last Completed Level"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="last_completed_level"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('adventure.levels')"
                        header-text="Total Levels"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="adventure.levels"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('rewards')"
                        header-text="Rewards Collected?"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="rewards"
                    />
                </x-data-tables.header>
                <x-data-tables.body>
                    @forelse($logs as $log)
                        <tr>
                            <td><a href="{{route('game.completed.adventure', [
                                        'adventureLog' => $log
                                    ])}}">{{$log->adventure->name}}</a></td>
                            <td>{{$log->complete ? 'Yes' : 'No'}}</td>
                            <td>{{$log->last_completed_level}}</td>
                            <td>{{$log->adventure->levels}}</td>
                            <td>{{is_null($log->rewards) ? 'Yes' : 'No'}}</td>
                        </tr>
                    @empty
                        <x-data-tables.no-results colspan="5" />
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-cards.card>
    </div>
</div>
