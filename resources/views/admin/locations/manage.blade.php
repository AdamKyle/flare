@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card wizard-content">
                <div class="card-body">
                    <h4 class="card-title">{{is_null($location) ? 'Create Location' : 'Edit Location: ' . $location->name}}</h4>
                    <form action="{{is_null($location) ? route('locations.store') : route('location.update', ['location' => $location->id])}}" method="POST" id="location-wizard" class="validation-wizard wizard-circle">
                        @csrf
                        
                        <!-- Step 1 -->
                        <h6>Location Details</h6>
                        <section>
                            @include('admin.locations.partials.location-details', [
                                'location'    => $location,
                                'maps'        => $maps,
                                'coordniates' => $coordinates,
                            ])
                        </section>
                        <!-- Step 2 -->
                        <h6>Location Quest Reward (Optional)</h6>
                        <section>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="quest-item">Select item as quest reward:</label>
                                        <select class="custom-select form-control" id="quest-item" name="quest_item_id">
                                            <option value="">Select Item</option>
                                            @foreach($items as $id => $name)
                                                <option value="{{$id}}" {{!is_null($location) ? ($location->quest_reward_item_id === $id ? 'selected' : '') : ''}}>{{$name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
