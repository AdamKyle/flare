@extends('layouts.app')

@section('content')
    <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
        <x-core.page-title
          title="Users"
          route="{{route('home')}}"
          link="Home"
          color="success"
        ></x-core.page-title>
        @livewire('admin.users.data-table')
    </div>
@endsection
