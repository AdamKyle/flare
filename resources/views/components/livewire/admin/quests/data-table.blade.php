<x-core.cards.card css="mt-5 w-full m-auto">
    <x-core.alerts.warning-alert title="Caution!">
        <p>Should an <a href="/information/npcs">NPC</a> offer any currency based quests, the currency quests will be done in order of currency from smallest to largest!</p>
        <p>The exception is if you have the specific item and the currency, although not if another currency quest (with no item) precedes it.</p>
        <p>You cannot select the quest to complete from the npc, they pick based on what you have on hand. It is suggested that players try and do
        quests as early on or they could regret it later. For example, for The Soldier, if you wanted The Creepy Baby Doll, you would have to do:
        Hunting Expedition followed by The Key to Disenchanting, before being able to get The Creepy Baby Doll.</p>
        <p>That's a total of 55k <a href="/information/currencies">Gold Dust</a> you need.</p>
    </x-core.alerts.warning-alert>
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
                            <a href="{{route('info.page.quest', ['quest' => $quest->id])}}">{{$quest->name}}
                            </a>
                        @else
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('quests.show', [
                                            'quest' => $quest->id
                                        ])}}">{{$quest->name}}</a>
                            @else
                                <a href="{{route('info.page.quest', ['quest' => $quest->id])}}">{{$quest->name}}</a>
                            @endif
                        @endguest
                    </td>
                    <td>{{$quest->npc_name}}</td>
                    <td>
                        @if (!is_null($quest->item))
                            {{$quest->item->name}}
                        @else
                            N/A
                        @endif
                    </td>
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
</x-core.cards.card>