@extends('layouts.admin')

@section('content')
    <div id="passive-skills-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/passive-skills/passive-skills-app.tsx')
@endpush
