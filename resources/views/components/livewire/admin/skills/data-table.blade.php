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
                                <a wire:click.prevent="sortBy('max_level')" role="button" href="#">
                                    Max Level
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'max_level'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('can_train')" role="button" href="#">
                                    Can train?
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'can_train'
                                    ])
                                </a>
                            </th>
                            <th>
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gameSkills as $gameSkill)
                            <tr>
                                <td><a href="{{route('skills.skill', [
                                    'skill' => $gameSkill->id
                                ])}}">{{$gameSkill->name}}</a></td>
                                <td>{{$gameSkill->max_level}}</td>
                                <td>{{$gameSkill->can_train ? 'Yes' : 'No'}}</td>
                                <td><a href="#" class="btn btn-primary btn-sm">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $gameSkills->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $gameSkills->firstItem() }} to {{ $gameSkills->lastItem() }} out of {{ $gameSkills->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
