@extends('layouts.admin')

@section('content')
    <div id="kingdom-buildings-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/kingdoms/buildings/buildings-app.tsx')
@endpush
