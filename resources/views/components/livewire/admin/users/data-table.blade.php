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
                        <input wire:model="search" class="form-control" type="text" placeholder="Search users...">
                    </div>
                </div>
                <table class="table table-bordered data-table">
                    <thead>
                        <tr>
                            <th>
                                <a wire:click.prevent="sortBy('id')" role="button" href="#">
                                    ID
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'id'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('characters.name')" role="button" href="#">
                                    Character Name
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'characters.name'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('is_banned')" role="button" href="#">
                                    Is Banned
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'is_banned'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('unbanned_at')" role="button" href="#">
                                    Unbanned at
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'unbanned_at'
                                    ])
                                </a>
                            </th>
                            <th>
                                <a wire:click.prevent="sortBy('user.currently_online')" role="button" href="#">
                                    Currently Online
                                    @include('admin.partials.data-table-icons', [
                                        'field' => 'user.currently_online'
                                    ])
                                </a>
                            </th>
                            <th>
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{$user->id}}</td>
                                <td><a href="{{route('users.user', [
                                    'user' => $user->id
                                ])}}">{{is_null($user->character) ? 'Admin' : $user->character->name}}</a></td>
                                <td>{{$user->is_banned ? 'Yes' : 'No'}}</td>
                                <td>{{$user->is_banned ? is_null($user->unbanned_at) ? 'Forever' : $user->unbanned_at->format('l jS \\of F Y h:i:s A') : 'N/A'}}</td>
                                <td>{{$user->currently_online ? 'Yes' : 'No'}}</td>
                                <td class="clearfix">
                                    @if (!is_null($user->character) && !$user->is_banned) 
                                        <a class="btn btn-sm btn-primary float-left mr-2" href="{{ route('user.reset.password', [
                                            'user' => $user->id
                                        ]) }}"
                                            onclick="event.preventDefault();
                                                        document.getElementById('{{'reset-password-' . $user->id}}').submit();">
                                            Reset Password
                                        </a>
                
                                        <form id="{{'reset-password-' . $user->id}}" action="{{ route('user.reset.password', [
                                            'user' => $user->id
                                        ]) }}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                        
                                        <div class="dropdown show float-left mr-2">
                                            <a class="btn btn-danger btn-sm dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                              Silence
                                            </a>
                                          
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                                <a class="dropdown-item" href="{{ route('user.silence', [
                                                    'user' => $user->id
                                                ]) }}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('{{'silence-user-10-' . $user->id}}').submit();">
                                                    10 Minutes
                                                </a>
                        
                                                <form id="{{"silence-user-10-".$user->id}}" action="{{ route('user.silence', [
                                                    'user' => $user->id
                                                ]) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="silence_for" value="10">
                                                </form>
                                                <a class="dropdown-item" href="{{ route('user.silence', [
                                                    'user' => $user->id
                                                ]) }}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('{{'silence-user-30-' . $user->id}}').submit();">
                                                    30 Minutes
                                                </a>
                        
                                                <form id="{{"silence-user-30-".$user->id}}" action="{{ route('user.silence', [
                                                    'user' => $user->id
                                                ]) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="silence_for" value="30">
                                                </form>
                                                <a class="dropdown-item" href="{{ route('user.silence', [
                                                    'user' => $user->id
                                                ]) }}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('{{'silence-user-60-' . $user->id}}').submit();">
                                                    60 Minutes
                                                </a>
                        
                                                <form id="{{"silence-user-60-".$user->id}}" action="{{ route('user.silence', [
                                                    'user' => $user->id
                                                ]) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="silence_for" value="60">
                                                </form>
                                            </div>
                                        </div>
                                        <div class="dropdown show float-left mr-2">
                                            <a class="btn btn-danger btn-sm dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                              Ban
                                            </a>
                                          
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                                <a class="dropdown-item" href="{{ route('ban.user', [
                                                    'user' => $user->id
                                                ]) }}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('{{'ban-user-1d-' . $user->id}}').submit();">
                                                    1 Day
                                                </a>
                        
                                                <form id="{{"ban-user-1d-".$user->id}}" action="{{ route('ban.user', [
                                                    'user' => $user->id
                                                ]) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="ban_for" value="one-day">
                                                </form>

                                                <a class="dropdown-item" href="{{ route('ban.user', [
                                                    'user' => $user->id
                                                ]) }}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('{{'ban-user-1w-' . $user->id}}').submit();">
                                                    1 Week
                                                </a>
                        
                                                <form id="{{"ban-user-1w-".$user->id}}" action="{{ route('ban.user', [
                                                    'user' => $user->id
                                                ]) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="ban_for" value="one-week">
                                                </form>

                                                <a class="dropdown-item" href="{{ route('ban.user', [
                                                    'user' => $user->id
                                                ]) }}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('{{'ban-user-perm-' . $user->id}}').submit();">
                                                    For ever
                                                </a>
                        
                                                <form id="{{"ban-user-perm-".$user->id}}" action="{{ route('ban.user', [
                                                    'user' => $user->id
                                                ]) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    <input type="hidden" name="ban_for" value="perm">
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <div class="col">
                        {{ $users->links() }}
                    </div>
            
                    <div class="col text-right text-muted">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} out of {{ $users->total() }} results
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
