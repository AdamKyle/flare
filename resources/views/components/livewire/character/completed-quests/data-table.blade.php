<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card additionalClasses="overflow-table">
            <div class="row pb-2">
                <x-data-tables.per-page wire:model="perPage" />
                <x-data-tables.search wire:model="search" />
            </div>
            <x-data-tables.table :collection="$quests">
                <x-data-tables.header>
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('name')"
                        header-text="Name"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="name"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('created_at')"
                        header-text="Completed At"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="created_at"
                    />
                </x-data-tables.header>
                <x-data-tables.body>
                    @forelse($quests as $log)
                        <tr wire:key="adventure-logs-table-{{$log->id}}">
                            <td><a href="{{route('completed.quest', [
                                'character' => $character,
                                'questsCompleted' => $log->id,
                            ])}}">{{$log->name}}</a></td>
                            <td>{{$log->created_at}}</td>
                        </tr>
                    @empty
                        <x-data-tables.no-results colspan="2" />
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-cards.card>
    </div>
</div>
