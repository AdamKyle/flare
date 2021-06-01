<div class="row page-titles">
    <div class="col-md-6 align-self-right">
        <h4 class="mt-2">{{$adventure->name}}</h4>
    </div>
    <div class="col-md-6 align-self-right">
        @guest
            <a href="{{ $customUrl ?? url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        @else
            @if (auth()->user()->hasRole('Admin'))
                <a href="{{route('adventures.list')}}" class="btn btn-primary float-right ml-2">Back</a>
            @else
                @guest
                    <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
                @else
                    @php
                        $url   = url()->previous();
                        $route = app('router')->getRoutes($url)->match(app('request')->create($url))->getName();
                    @endphp

                    @if ($route === 'info.page')
                        <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
                    @else
                        <a href="{{route('game')}}" class="btn btn-primary float-right ml-2">Back</a>
                    @endif
                @endguest
            @endif
        @endGuest
    </div>
</div>
@include('admin.adventures.partials.adventure-base', [
    'adventure' => $adventure
])
