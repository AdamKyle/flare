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
                                <a wire:click.prevent="sortBy('default')" role="button" href="#">
                                    Default Starting Map
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'default'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('characters_using')" role="button" href="#">
                                    Characters Using<sup>*</sup>
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'characters_using'
                                    ])
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($maps as $map)
                            <tr>
                                <td>
                                   {{$map->name}}
                                </td>
                                <td>{{$map->default ? 'Yes' : 'No'}}</td>
                                <td>{{$map->characters_using}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $maps->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $maps->firstItem() }} to {{ $maps->lastItem() }} out of {{ $maps->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
