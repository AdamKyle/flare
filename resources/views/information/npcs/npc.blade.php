@extends('layouts.information')

@section('content')
    <div class="w-full lg:w-3/4 m-auto mt-20 mb-10">
        <x-core.page-title
            title="{{$npc->name}}"
            route="{{url()->previous()}}"
            link="Back"
            color="primary"
        ></x-core.page-title>

        <hr />
        @include('admin.npcs.partials.show', ['npc' => $npc])
    </div>
@endsection
