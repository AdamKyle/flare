<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$gameSkills">
                    <x-data-tables.header>
                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('name')" 
                            header-text="Name" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('max_level')" 
                            header-text="Max Level" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="max_level"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('can_train')" 
                            header-text="Can Train" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="can_train"
                        />

                        @guest
                        @else
                            @if (auth()->user()->hasRole('Admin'))
                                <x-data-tables.header-row 
                                    header-text="Actions" 
                                />
                            @endif
                        @endguest
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($gameSkills as $gameSkill)
                            <tr>
                                <td>
                                    @guest
                                        <a href="{{route('info.page.skill', [
                                            'skill' => $gameSkill->id
                                        ])}}">{{$gameSkill->name}}</a>
                                    @else
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('skills.skill', [
                                                'skill' => $gameSkill->id
                                            ])}}">{{$gameSkill->name}}</a>
                                        @endif
                                    @endguest
                                </td>
                                <td>{{$gameSkill->max_level}}</td>
                                <td>{{$gameSkill->can_train ? 'Yes' : 'No'}}</td>
                                @if (!is_null(auth()->user()))
                                    @if (auth()->user()->hasRole('Admin'))
                                        <td><a href="{{route('skill.edit', [
                                            'skill' => $gameSkill->id
                                        ])}}" class="btn btn-primary btn-sm">Edit</a></td>
                                    @endif
                                @endif
                            </tr>
                        @empty
                            @guest
                                <x-data-tables.no-results colspan="3" />
                            @else
                                @if (auth()->user()->hasRole('Admin'))
                                    <x-data-tables.no-results colspan="4" />
                                @endif
                            @endguest
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
