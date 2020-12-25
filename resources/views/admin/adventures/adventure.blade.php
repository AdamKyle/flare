@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$adventure->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            @guest
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            @else
                @if (auth()->user()->hasRole('Admin'))
                    <a href="{{route('adventures.list')}}" class="btn btn-primary float-right ml-2">Back</a>
                @else
                    <a href="{{route('game')}}" class="btn btn-primary float-right ml-2">Back</a>
                @endif
                
            @endGuest
        </div>
    </div>
    @include('admin.adventures.partials.adventure-base', [
        'adventure' => $adventure
    ])
@endsection
