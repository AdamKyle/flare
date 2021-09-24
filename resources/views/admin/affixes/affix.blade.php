@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$itemAffix->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-success float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    @include('admin.affixes.partials.affix-details', ['itemAffix' => $itemAffix])
@endsection
