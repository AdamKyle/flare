@extends('layouts.admin')

@section('content')
    <div id="gem-abilities-admin-app"></div>
@endsection

@push('scripts')
    @vite('resources/js/admin/gem-abilities/gem-abilities-app.tsx')
@endpush
