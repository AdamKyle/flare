@extends('layouts.app')

@section('content')
    <x-core.page-title title="Location Gems" route="{{ route('info.page', ['pageName' => 'home']) }}" color="success" link="Information" />

    @livewire('info.location-gems')
@endsection
