@extends('layouts.admin')

@section('content')
    <div id="items-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/items/items-app.tsx')
@endpush
