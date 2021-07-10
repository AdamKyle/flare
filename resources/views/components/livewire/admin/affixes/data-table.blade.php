<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$itemAffixes">
                    <x-data-tables.header>
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('name')"
                            header-text="Name"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('type')"
                            header-text="Type"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="type"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('base_damage_mod')"
                            header-text="Base Damage Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_damage_mod"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('base_ac_mod')"
                            header-text="Base AC Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_ac_mod"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('base_healing_mod')"
                            header-text="Base Healing Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_healing_mod"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('int_required')"
                            header-text="Int Required"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="int_required"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('cost')"
                            header-text="Cost"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="cost"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('skill_level_required')"
                            header-text="Skill Level Required"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="skill_level_required"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('skill_level_trivial')"
                            header-text="Skill Level Trivial"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="skill_level_trivial"
                        />

                        <x-data-tables.header-row
                            header-text="Actions"
                        />
                    </x-data-tables.header>
                    <x.data-tables.body>
                        @foreach($itemAffixes as $itemAffix)
                            <tr>
                                @guest
                                    <td><a href="{{route('info.page.affix', [
                                    'affix' => $itemAffix->id
                                    ])}}">{{$itemAffix->name}}</a></td>
                                @elseif (auth()->user()->hasRole('Admin'))
                                    <td><a href="{{route('affixes.affix', [
                                        'affix' => $itemAffix->id
                                    ])}}">{{$itemAffix->name}}</a></td>
                                @else
                                    @if ($previousUrlIsInfo = strpos(url()->previous(), 'information') !== false)
                                        <td><a href="{{route('info.page.affix', [
                                            'affix' => $itemAffix->id
                                        ])}}">{{$itemAffix->name}}</a></td>
                                    @else
                                        <td><a href="{{route('game.affixes.affix', [
                                            'affix' => $itemAffix->id
                                        ])}}">{{$itemAffix->name}}</a></td>
                                    @endif


                                @endguest
                                <td>{{$itemAffix->type}}</td>
                                <td>{{is_null($itemAffix->base_damage_mod) ? 'N/A' : ($itemAffix->base_damage_mod * 100) . '%'}}</td>
                                <td>{{is_null($itemAffix->base_ac_mod) ? 'N/A' : ($itemAffix->base_ac_mod * 100) . '%'}}</td>
                                <td>{{is_null($itemAffix->base_healing_mod) ? 'N/A' : ($itemAffix->base_healing_mod * 100) . '%'}}</td>
                                <td>{{is_null($itemAffix->int_required) ? 'N/A' : $itemAffix->int_required}}</td>
                                <td>{{is_null($itemAffix->cost) ? 'N/A' : number_format($itemAffix->cost)}}</td>
                                <td>{{is_null($itemAffix->skill_level_required) ? 'N/A' : $itemAffix->skill_level_required}}</td>
                                <td>{{is_null($itemAffix->skill_level_trivial) ? 'N/A' : $itemAffix->skill_level_trivial}}</td>
                                <td>
                                    @guest
                                    @else
                                        @if(auth()->user()->hasRole('Admin'))
                                            <x-buttons.simple-button
                                                button-route="{{route('affixes.edit', [
                                                    'affix' => $itemAffix->id
                                                ])}}"
                                                button-title="Edit"
                                                class="btn btn-primary btn-sm"
                                            />

                                            <x-forms.button-with-form
                                                form-route="{{route('affixes.delete', [
                                                    'affix' => $itemAffix->id
                                                ])}}"
                                                form-id="{{'delete-item-affix-'.$itemAffix->id}}"
                                                button-title="Delete"
                                                class="btn btn-danger btn-sm"
                                            />
                                        @endif
                                    @endguest
                                </td>
                            </tr>
                        @endforeach
                    </x.data-tables.body>
                </x-data-tables.table>
                <div class="mb-2 mt-2 text-muted">
                    <sup>*</sup> <em><strong>Cost</strong>: refers to enchanting cost.</em>
                </div>
            </div>
        </div>
    </div>
</div>
