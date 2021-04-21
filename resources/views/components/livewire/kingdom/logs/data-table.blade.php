<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card additionalClasses="overflow-table">
            <div class="row pb-2">
                <x-data-tables.per-page wire:model="perPage" />
                <x-data-tables.search wire:model="search" />
            </div>
            @include('components.livewire.kingdom.logs.partials.batch-delete', [
                'character' => $character,
                'selected'  => $selected,
            ])
            <x-data-tables.table :collection="$logs">
                <x-data-tables.header>
                    <x-data-tables.header-row>
                        <input type="checkbox" wire:model="pageSelected" id="select-all" />
                    </x-data-tables.header-row>

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('status')"
                        header-text="Name"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="status"
                    />
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('from_kingdom_name')"
                        header-text="From Kingdom"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="from_kingdom_name"
                    />
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('to_kingdom_name')"
                        header-text="Kingdom Attacked"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="to_kingdom_name"
                    />
                    <x-data-tables.header-row>
                        Actions
                    </x-data-tables.header-row>
                </x-data-tables.header>
                <x-data-tables.body>
                    @if ($pageSelected)
                        <tr>
                            <td colspan="8">
                                @unless($selectAll)
                                    <div>
                                        <span>You have selected <strong>{{$logs->count()}}</strong> items of <strong>{{$logs->total()}}</strong>. Would you like to select all?</span>
                                        <button class="btn btn-link" wire:click="selectAll">Select all</button>
                                    </div>
                                @else
                                    <span>You are currently selecting all <strong>{{$logs->total()}}</strong> items.</span>
                                @endunless
                            </td>
                        </tr>
                    @endif

                    @forelse($logs as $log)
                        <tr wire:key="attack-logs-table-{{$log->id}}">
                            <td>
                                <input type="checkbox" wire:model="selected" value="{{$log->id}}"/>
                            </td>
                            <td><a href="#">{{$log->status}}</a></td>
                            <td>{{$log->from_kingdom_name}}</td>
                            <td>{{$log->to_kingdom_name}}</td>
                            <td>
                                <x-forms.button-with-form
                                    formRoute="{{route('game.kingdom.delete-log', [
                                        'character'  => $character,
                                        'kingdomLog' => $log,
                                    ])}}"
                                    formId="delete-log-{{$log->id}}"
                                    buttonTitle="Delete Log"
                                    class="btn btn-danger"
                                >
                                </x-forms.button-with-form>
                            </td>
                        </tr>
                    @empty
                        <x-data-tables.no-results colspan="4" />
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-cards.card>
    </div>
</div>
