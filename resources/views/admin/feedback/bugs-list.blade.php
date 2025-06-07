@extends('layouts.app')

@section('content')
    <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <div class="tw-m-auto">
            <x-core.page-title
                title="Bugs"
                route="{{route('home')}}"
                link="Home"
                color="success"
            ></x-core.page-title>
        </div>
        @livewire('admin.feedback.BugsList')
    </div>
@endsection
