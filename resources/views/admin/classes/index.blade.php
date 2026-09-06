@extends('layouts.admin')

@section('content')
    <div id="classes-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/classes/classes-app.tsx')
@endpush
