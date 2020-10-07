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
                        <input wire:model="search" class="form-control" type="text" placeholder="Search monsters...">
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
                                <a wire:click.prevent="sortBy('max_level')" role="button" href="#">
                                    Max level
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'max_level'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('damage_stat')" role="button" href="#">
                                    Damage Stat
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'damage_stat'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('health_range')" role="button" href="#">
                                    Health Range
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'health_range'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('attack_range')" role="button" href="#">
                                    Attack Range
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'attack_range'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('xp')" role="button" href="#">
                                    XP
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'xp'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('gold')" role="button" href="#">
                                    Gold
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'gold'
                                    ])
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monsters as $monster)
                            <tr>
                                <td>
                                    @if (auth()->user()->hasRole('Admin'))))
                                        <a href="{{route('monsters.monster', [
                                            'monster' => $monster->id
                                        ])}}">{{$monster->name}}</a>
                                    @else
                                        <a href="{{route('game.monsters.monster', [
                                            'monster' => $monster->id
                                        ])}}">{{$monster->name}}</a>
                                    @endif
                                </td>
                                <td>{{$monster->max_level}}</td>
                                <td>{{$monster->damage_stat}}</td>
                                <td>{{$monster->health_range}}</td>
                                <td>{{$monster->attack_range}}</td>
                                <td>{{$monster->xp}}</td>
                                <td>{{$monster->gold}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $monsters->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $monsters->firstItem() }} to {{ $monsters->lastItem() }} out of {{ $monsters->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
