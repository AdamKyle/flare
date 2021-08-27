@extends('layouts.information')

@section('content')
    <div class="row page-titles mt-3">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$quest->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    @include('admin.quests.partials.show', ['quest' => $quest, 'lockedSkill' => $lockedSkill])
@endsection
