@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-2 mt-2">
        @if (is_array(session('success')))
            <h5 class="ml-2" style="color: #486353;">Adventure Rewards Collected!</h5>
            <ul>
                @foreach(session('success') as $message) 
                    <li>{{$message}}</li>
                @endforeach
            </ul>
        @else
            {{ session('success') }}
        @endif
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
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
