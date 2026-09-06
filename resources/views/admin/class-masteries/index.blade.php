@extends('layouts.admin')

@section('content')
    <div id="class-masteries-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/class-masteries/class-masteries-app.tsx')
@endpush
