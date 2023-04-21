@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        @php
            $backUrl = route('races.list');

            if (is_null(auth()->user())) {
                $backUrl = '/information/races-and-classes';
            } else if (!auth()->user()->hasRole('Admin')) {
                $backUrl = '/information/races-and-classes';
            }
        @endphp

        <x-core.cards.card-with-title
            title="{{$race->name}}"
            buttons="true"
            backUrl="{{$backUrl}}"
            editUrl="{{route('races.edit', ['race' => $race->id])}}"
        >
            <dl>
                <dt>Strength Modifier</dt>
                <dd>+ {{$race->str_mod}} pts.</dd>
                <dt>Durability Modifier</dt>
                <dd>+ {{$race->dur_mod}} pts.</dd>
                <dt>Dexterity Modifier</dt>
                <dd>+ {{$race->dex_mod}} pts.</dd>
                <dt>Intelligence Modifier</dt>
                <dd>+ {{$race->int_mod}} pts.</dd>
                <dt>Charisma Modifier</dt>
                <dd>+ {{$race->chr_mod}} pts.</dd>
                <dt>Accuracy Modifier</dt>
                <dd>+ {{$race->accuracy_mod * 100}} %</dd>
                <dt>Dodge Modifier</dt>
                <dd>+ {{$race->dodge_mod * 100}} %</dd>
                <dt>Looting Modifier</dt>
                <dd>+ {{$race->looting_mod * 100}} %</dd>
                <dt>Defense Modifier</dt>
                <dd>+ {{$race->defense_mod * 100}} %</dd>
            </dl>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
