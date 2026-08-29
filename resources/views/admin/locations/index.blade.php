@extends('layouts.admin')

@section('content')
    <div id="locations-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/locations/locations-app.tsx')
@endpush
