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
                            wire:click.prevent="sortBy('cost')" 
                            header-text="Cost" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="cost"
                        />

                        <x-data-tables.header-row 
                            header-text="Actions" 
                        />
                    </x-data-tables.header>
                    <x.data-tables.body>
                        @foreach($itemAffixes as $itemAffix)
                            <tr>
                                <td><a href="{{route('affixes.affix', [
                                    'affix' => $itemAffix->id
                                ])}}">{{$itemAffix->name}}</a></td>
                                <td>{{$itemAffix->type}}</td>
                                <td>{{is_null($itemAffix->base_damage) ? 'N/A' : $itemAffix->base_damage}}</td>
                                <td>{{is_null($itemAffix->base_ac) ? 'N/A' : $itemAffix->base_ac}}</td>
                                <td>{{is_null($itemAffix->base_healing) ? 'N/A' : $itemAffix->base_healing}}</td>
                                <td>{{is_null($itemAffix->cost) ? 'N/A' : $itemAffix->cost}}</td>
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
