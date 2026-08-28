@extends('layouts.admin')

@section('content')
    <div id="game-maps-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/game-maps/game-maps-app.tsx')
@endpush
