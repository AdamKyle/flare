@extends('layouts.admin')

@section('content')
    <div id="races-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/races/races-app.tsx')
@endpush
