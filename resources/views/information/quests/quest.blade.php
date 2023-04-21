@extends('layouts.information')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="{{$quest->name}}"
            route="{{url()->previous()}}"
            link="Back"
            color="primary"
        ></x-core.page-title>

        <div class="m-auto">
            <x-core.cards.card>
                @include('admin.quests.partials.show', ['quest' => $quest, 'lockedSkill' => $lockedSkill])
            </x-core.cards.card>
        </div>
    </x-core.layout.info-container>
@endsection
