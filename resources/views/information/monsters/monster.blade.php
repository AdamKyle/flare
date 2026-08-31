@extends('layouts.information')

@section('content')
    <div class="m-auto" id="monster-info-app" data-monster-id="{{ $monster->id }}"></div>
@endsection

@push('scripts')
    @vite('resources/js/information/monsters/monsters-info-app.tsx')
@endpush
