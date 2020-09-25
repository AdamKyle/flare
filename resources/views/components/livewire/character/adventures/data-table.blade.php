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
                        <input wire:model="search" class="form-control" type="text" placeholder="Search adventure logs...">
                    </div>
                </div>
                <table class="table table-bordered data-table">
                    <thead>
                        <tr>
                            <th>
                                <a wire:click.prevent="sortBy('adventure.name')" role="button" href="#">
                                    Name
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'adventure.name'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('complete')" role="button" href="#">
                                    Complete
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'complete'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('last_completed_level')" role="button" href="#">
                                    Last level Completed
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'last_completed_level'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('adventure.levels')" role="button" href="#">
                                    Adventure Levels
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'adventure.levels'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('rewards')" role="button" href="#">
                                    Reward Collected
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'rewards'
                                    ])
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td><a href="{{route('game.completed.adventure', [
                                    'adventureLog' => $log
                                ])}}">{{$log->adventure->name}}</a></td>
                                <td>{{$log->complete ? 'Yes' : 'No'}}</td>
                                <td>{{$log->last_completed_level}}</td>
                                <td>{{$log->adventure->levels}}</td>
                                <td>{{is_null($log->rewards) ? 'Yes' : 'No'}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
