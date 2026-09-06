@extends('layouts.admin')

@section('content')
    <div id="map-gems-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/map-gems/map-gems-app.tsx')
@endpush
