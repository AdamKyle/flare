@extends('layouts.admin')

@section('content')
    <div id="location-gems-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/location-gems/location-gems-app.tsx')
@endpush
