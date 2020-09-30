<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col form-inline">
                        Per Page: &nbsp;
                        <select wire:model="perPage" class="form-control">
                            <option>10</option>
                            <option>15</option>
                            <option>25</option>
                        </select>
                    </div>
            
                    <div class="col">
                        <input wire:model="search" class="form-control" type="text" placeholder="Search items...">
                    </div>
                </div>
                <table class="table table-bordered data-table">
                    <thead>
                        <tr>
                            <th>
                                <a wire:click.prevent="sortBy('name')" role="button" href="#">
                                    Name
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'name'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('type')" role="button" href="#">
                                    Type
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'type'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('base_damage_mod')" role="button" href="#">
                                    Base Damage Mod
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'base_damage_mod'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('base_ac_mod')" role="button" href="#">
                                    Base AC Mod
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'base_ac_mod'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('base_healing_mod')" role="button" href="#">
                                    Base Healing Mod
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'base_healing_mod'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('cost')" role="button" href="#">
                                    Cost<sup>*</sup>
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'cost'
                                    ])
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                    @if(auth()->user()->hasRole('Admin'))
                                        <a class="btn btn-danger" href="{{route('affixes.delete', [
                                            'affix' => $itemAffix->id
                                        ])}}"
                                                        onclick="event.preventDefault();
                                                        document.getElementById('delete-item-affix-{{$itemAffix->id}}').submit();">
                                            {{ __('Delete') }}
                                        </a>

                                        <form id="delete-item-affix-{{$itemAffix->id}}" action="{{route('affixes.delete', [
                                            'affix' => $itemAffix->id
                                        ])}}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mb-2 mt-2 text-muted">
                    <sup>*</sup> <em>Refers to enchanting cost.</em>
                </div>
                <div class="row">
                    <div class="col">
                        {{ $itemAffixes->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $itemAffixes->firstItem() }} to {{ $itemAffixes->lastItem() }} out of {{ $itemAffixes->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
