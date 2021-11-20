<div class="row justify-content-center">
    <div class="col-md-12">
        <x-core.cards.card css="tw-mt-5 tw-w-full lg:tw-w-3/4 tw-m-auto">
            <div class="row pb-2">
                <x-data-tables.per-page wire:model="perPage" />
                <x-data-tables.search wire:model="search" />
            </div>
            @include('components.livewire.character.adventures.partials.batch-delete', [
                'character' => $character,
                'selected'  => $selected,
            ])
            <x-data-tables.table :collection="$logs">
                <x-data-tables.header>
                    <x-data-tables.header-row>
                        <input type="checkbox" wire:model="pageSelected" id="select-all" />
                    </x-data-tables.header-row>

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
                        <tr wire:key="adventure-logs-table-{{$log->id}}">
                            <td>
                                <input type="checkbox" wire:model="selected" value="{{$log->id}}"/>
                            </td>
                            <td>
                                @if ($log->in_progress)
                                    {{$log->adventure->name}} <em>(Currently in progress)</em>
                                @else
                                    <a href="{{route('game.completed.adventure', [
                                        'adventureLog' => $log
                                    ])}}">{{$log->adventure->name}}</a>
                                @endif
                            </td>
                            <td>{{$log->complete ? 'Yes' : 'No'}}</td>
                            <td>{{$log->last_completed_level}}</td>
                            <td>{{$log->adventure->levels}}</td>
                            <td>{{is_null($log->rewards) ? 'Yes' : 'No'}}</td>
                            <td>
                                <x-forms.button-with-form
                                    formRoute="{{route('game.adventures.delete', [
                                        'adventureLog' => $log,
                                    ])}}"
                                    formId="delete-log-{{$log->id}}"
                                    buttonTitle="Delete Log"
                                    class="btn btn-danger"
                                >
                                </x-forms.button-with-form>
                            </td>
                        </tr>
                    @empty
                        <x-data-tables.no-results colspan="7" />
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-core.cards.card>>
    </div>
</div>
