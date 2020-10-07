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
                        <input wire:model="search" class="form-control" type="text" placeholder="Search locations...">
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
                                <a wire:click.prevent="sortBy('game_maps.name')" role="button" href="#">
                                    Map
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'game_maps.name'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('x')" role="button" href="#">
                                    X Coordinate
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'x'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('y')" role="button" href="#">
                                    Y Coordinate
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'y'
                                    ])
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locations as $location)
                            <tr>
                                <td>
                                    @if (auth()->user()->hasRole('Admin'))
                                        <a href="{{route('locations.location', [
                                            'location' => $location->id
                                        ])}}">{{$location->name}}</a>
                                    @else
                                        <a href="{{route('game.locations.location', [
                                            'location' => $location->id
                                        ])}}">{{$location->name}}</a>
                                    @endif
                                   
                                </td>
                                <td>{{$location->map->name}}</td>
                                <td>{{$location->x}}</td>
                                <td>{{$location->y}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $locations->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $locations->firstItem() }} to {{ $locations->lastItem() }} out of {{ $locations->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
