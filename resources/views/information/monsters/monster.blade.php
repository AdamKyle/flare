@extends('layouts.information', [
    'pageTitle' => 'Monster'
])

@section('content')
    <div class="mt-3">
        @include('admin.monsters.partials.monster', [
            'monster' => $monster,
        ])
    </div>
@endsection
