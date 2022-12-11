@extends('layouts.information')

@section('content')
    @include('admin.monsters.partials.monster', [
        'monster' => $monster,
    ])
@endsection
