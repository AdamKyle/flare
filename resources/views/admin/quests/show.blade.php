@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$quest->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                @if (auth()->user()->hasRole('isAdmin'))
                    <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
                @else
                    <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
                @endif
            </div>
        </div>
        <hr />
        @include('admin.quests.partials.show', ['quest' => $quest])
    </div>
@endsection
