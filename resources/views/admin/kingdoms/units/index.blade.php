@extends('layouts.admin')

@section('content')
    <div id="kingdom-units-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/kingdoms/units/units-app.tsx')
@endpush
