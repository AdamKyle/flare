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
                        <input wire:model="search" class="form-control" type="text" placeholder="Search adventures...">
                    </div>
                </div>
                <table class="table table-bordered">
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
                                <a wire:click.prevent="sortBy('levels')" role="button" href="#">
                                    Total Levels
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'levels'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('time_per_level')" role="button" href="#">
                                    Time Per Level
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'time_per_level'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('gold_rush_chance')" role="button" href="#">
                                    Gold Rush Chance
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'gold_rush_chance'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('item_find_chance')" role="button" href="#">
                                    Item Find Chance
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'item_find_chance'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('skill_exp_bonus')" role="button" href="#">
                                    Skill XP Bonus
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'skill_exp_bonus'
                                    ])
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adventures as $adventure)
                            <tr>
                                <td>
                                    @guest
                                        <a href="{{route('info.page.adventure', [
                                            'adventure' => $adventure->id
                                        ])}}">{{$adventure->name}}</a>
                                    @else
                                        <a href="{{route('adventures.adventure', [
                                            'adventure' => $adventure->id
                                        ])}}">{{$adventure->name}}</a>
                                    @endif
                                </td>
                                <td>{{$adventure->levels}}</td>
                                <td>{{$adventure->time_per_level}} Minutes</td>
                                <td>{{$adventure->gold_rush_chance * 100}}%</td>
                                <td>{{$adventure->item_find_chance * 100}}%</td>
                                <td>{{$adventure->skill_exp_bonus * 100}}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $adventures->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $adventures->firstItem() }} to {{ $adventures->lastItem() }} out of {{ $adventures->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
