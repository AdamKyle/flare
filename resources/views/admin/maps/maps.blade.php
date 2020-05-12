@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            Maps Information:
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Maps</h4>

                    <table class="table table-bordered text-center">
                        <thead class="thead-dark">
                            <tr>
                                <th>Name</th>
                                <th>Default Map</th>
                                <th>Total Characters Using</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($maps as $map)
                                <tr>
                                    <td>{{$map->name}}</td>
                                    <td>{{$map->default ? 'Yes' : 'No'}}</td>
                                    <td>{{$map->maps->count()}}</td>
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
