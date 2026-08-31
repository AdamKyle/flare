@extends('layouts.admin')

@section('content')
    <div id="monsters-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/monsters/monsters-app.tsx')
@endpush
