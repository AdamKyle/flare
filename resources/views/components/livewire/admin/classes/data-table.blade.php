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
                        <input wire:model="search" class="form-control" type="text" placeholder="Search races...">
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
                                <a wire:click.prevent="sortBy('damage_stat')" role="button" href="#">
                                    Damage Stat
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'damage_stat'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('str_mod')" role="button" href="#">
                                    Strength Modifier
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'str_mod'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('dur_mod')" role="button" href="#">
                                    Durability Modifier
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'dur_mod'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('dex_mod')" role="button" href="#">
                                    Dexterity Modifier
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'dex_mod'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('chr_mod')" role="button" href="#">
                                    Charisma Modifier
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'chr_mod'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('int_mod')" role="button" href="#">
                                    Intelligence Modifier
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'int_mod'
                                    ])
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gameClasses as $class)
                            <tr>
                                <td>
                                    @if (!is_null(auth()->user()))
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('classes.class', [
                                                'class' => $class->id
                                            ])}}">
                                                {{$class->name}}
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{route('info.page.class', [
                                            'class' => $class
                                        ])}}">
                                            {{$class->name}}
                                        </a> 
                                    @endif
                                </td>
                                <td>{{$class->damage_stat}}</td>
                                <td>{{$class->str_mod}}</td>
                                <td>{{$class->dur_mod}}</td>
                                <td>{{$class->dex_mod}}</td>
                                <td>{{$class->chr_mod}}</td>
                                <td>{{$class->int_mod}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $gameClasses->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $gameClasses->firstItem() }} to {{ $gameClasses->lastItem() }} out of {{ $gameClasses->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
