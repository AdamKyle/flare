@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Inventory</h4>

                    <table class="table table-bordered text-center">
                        <thead class="thead-dark">
                            <tr>
                                <th>Name</th>
                                <th>Base Damage</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($inventory as $slot)
                                <tr>
                                    <td>{{$slot->item->name}}</td>
                                    <td>{{$slot->item->base_damage}}</td>
                                    <td>{{$slot->item->cost}}</td>
                                    <td><a href="#" class="btn btn-primary">Sell</a></td>
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
