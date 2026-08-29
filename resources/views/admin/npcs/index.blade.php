@extends('layouts.admin')

@section('content')
    <div id="npcs-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/npcs/npcs-app.tsx')
@endpush
