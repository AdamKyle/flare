@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <div id="info-management" data-info-id="{{ $infoPageId }}"></div>
    </x-core.layout.info-container>
@endsection
