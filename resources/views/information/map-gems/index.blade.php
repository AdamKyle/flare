@extends('layouts.app')

@section('content')
    <x-core.page-title title="Map Gems" route="{{ route('info.page', ['pageName' => 'home']) }}" color="success" link="Information" />

    @livewire('info.map-gems')
@endsection
