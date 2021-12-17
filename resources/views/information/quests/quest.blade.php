@extends('layouts.information')

@section('content')
    <div class="mt-20 mb-10 w-full lg:w-3/5 m-auto">
        <div class="m-auto">
            <x-core.page-title
              title="{{$quest->name}}"
              route="{{url()->previous()}}"
              link="Back"
              color="primary"
            ></x-core.page-title>
        </div>
        <hr />
        <x-core.alerts.warning-alert title="Caution!">
            <p>Should an <a href="/information/npcs">NPC</a> offer any currency based quests, the currency quests will be done in order of currency from smallest to largest!</p>
            <p>The exception is if you have the specific item and the currency, although not if another currency quest (with no item) precedes it.</p>
            <p>You cannot select the quest to complete from the npc, they pick based on what you have on hand. It is suggested that players try and do
                quests as early on or they could regret it later. For example, for The Soldier, if you wanted The Creepy Baby Doll, you would have to do:
                Hunting Expedition followed by The Key to Disenchanting, before being able to get The Creepy Baby Doll.</p>
            <p>That's a total of 55k <a href="/information/currencies">Gold Dust</a> you need.</p>
        </x-core.alerts.warning-alert>
        <div class="m-auto">
            @include('admin.quests.partials.show', ['quest' => $quest, 'lockedSkill' => $lockedSkill])
        </div>
    </div>
@endsection
