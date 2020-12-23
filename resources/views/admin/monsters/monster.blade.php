@extends('layouts.app')

@section('content')

<div class="container-fluid ">
    <div class="container justify-content-center">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$monster->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>
        @include('admin.monsters.partials.details', [
            'monster' => $monster,
            'canEdit' => true,
        ])
    </div>
</div>
@endsection
