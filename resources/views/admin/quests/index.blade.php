@extends('layouts.admin')

@section('content')
    <div id="quests-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/quests/quests-app.tsx')
@endpush
