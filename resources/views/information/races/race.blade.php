@extends('layouts.information', [
    'pageTitle' => 'character-information'
])

@section('content')
    <div class="mt-5">
        @include('admin.races.race', [
            'race' => $race,
            'customClass' => 'mt-5'
        ])
    </div>
@endsection
