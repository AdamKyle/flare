@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{$building->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-success float-right ml-2">Back</a>
            @guest
            @else
                @if (auth()->user()->hasRole('Admin'))
                    <a href="{{route('buildings.edit', [
                        'building' => $building->id
                    ])}}" class="btn btn-primary float-right ml-2">Edit</a>
                @endif
            @endguest
        </div>
    </div>
    <hr />
    @include('admin.kingdoms.buildings.partials.building-base', [
        'building' => $building
    ])

    @include('admin.kingdoms.buildings.partials.building-attributes', [
        'building' => $building
    ])
@endsection
