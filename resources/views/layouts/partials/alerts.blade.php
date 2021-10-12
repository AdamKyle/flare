@if(session('collected-rewards'))
    <div class="alert alert-success alert-dismissible fade show mb-2 mt-2">
        <h5 class="ml-2" style="color: #486353;">Adventure Rewards Collected!</h5>
        <ul>
            @foreach(Cache::pull('messages-' . session('collected-rewards')) as $message)
                <li>{{$message}}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-2 mt-2">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-2 mt-2">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-2 mt-2">
        @foreach($errors->all() as $error)
            {{ $error }} <br />
        @endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
