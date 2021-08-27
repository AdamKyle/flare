<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
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
                            wire:click.prevent="sortBy('npc_id')"
                            header-text="Given By NPC"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="npc_id"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('item_id')"
                            header-text="Required Item"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="item_id"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('gold_cost')"
                            header-text="Gold Cost"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="gold_cost"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('gold_dust_cost')"
                            header-text="Gold Dust Cost"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="gold_dust_cost"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('shards_cost')"
                            header-text="Shards Cost"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="shards_cost"
                        />
                        @auth
                            @if (auth()->user()->hasRole('Admin'))
                                <x-data-tables.header-row>
                                    Actions
                                </x-data-tables.header-row>
                            @endif
                        @endauth
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($quests as $quest)
                            <tr>
                                <td>
                                    @guest
                                        <a href="{{route('information.quests.quest', ['quest' => $quest->id])}}">{{$quest->name}}
                                        </a>
                                    @else
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('quests.show', [
                                                'quest' => $quest->id
                                            ])}}">{{$quest->name}}</a>
                                        @else
                                            <a href="{{route('information.quests.quest', ['quest' => $quest->id])}}">{{$quest->name}}</a>
                                        @endif
                                    @endguest
                                </td>
                                <td>{{$quest->npc_name}}</td>
                                <td>{{$quest->item->name}}</td>
                                <td>{{number_format($quest->gold_cost)}}</td>
                                <td>{{number_format($quest->gold_dust_cost)}}</td>
                                <td>{{number_format($quest->shards_cost)}}</td>
                                @guest
                                @else
                                    <td>
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('quests.edit', [
                                                    'quest' => $quest->id,
                                            ])}}" class="btn btn-primary mt-2">Edit</a>
                                        @endif
                                    </td>
                                @endguest
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="7" />
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
