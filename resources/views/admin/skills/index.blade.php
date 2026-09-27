@extends('layouts.admin')

@section('content')
    <div id="skills-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/skills/skills-app.tsx')
@endpush
