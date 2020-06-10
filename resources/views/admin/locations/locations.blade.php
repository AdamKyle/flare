@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-right">
            <a href="{{route('locations.create')}}" class="btn btn-primary float-right ml-2">Create</a>
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Locations</h4>

                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Map Name</th>
                                <th>Position (X/Y)</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($locations as $location)
                                <tr>
                                    <td><a href="{{route('locations.location', ['location' => $location])}}">{{$location->name}}</a></td>
                                    <td>{{$location->description}}</td>
                                    <td>{{$location->map->name}}</td>
                                    <td>{{$location->x}}/{{$location->y}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
