@extends('layouts.app')

@section('content')
    <div class="w-full lg:w-3/4 m-auto">
        <x-core.page-title
            title="Users"
            route="{{route('home')}}"
            link="Home"
            color="success"
        ></x-core.page-title>
        @livewire('admin.users.user-table')
    </div>
@endsection
