@extends('layouts.admin')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title title="Delve Monitoring" route="{{ route('home') }}" link="Back" />

        <div id="delve-monitoring"></div>
    </x-core.layout.info-container>
@endsection

@push('scripts')
    @vite('resources/js/admin/monitoring/delve-monitoring.tsx')
@endpush
