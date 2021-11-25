@extends('layouts.app')

@section('content')
    <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <div class="tw-m-auto">
            <x-core.page-title
              title="{{$race->name}}"
              route="{{url()->previous()}}"
              link="Back"
              color="primary"
            ></x-core.page-title>
        </div>
        <hr />
        <x-core.cards.card>
            <dl>
                <dt>Strength Mofidfier</dt>
                <dd>+ {{$race->str_mod}} pts.</dd>
                <dt>Durability Modifier</dt>
                <dd>+ {{$race->dur_mod}} pts.</dd>
                <dt>Dexterity Modifier</dt>
                <dd>+ {{$race->dex_mod}} pts.</dd>
                <dt>Intelligence Modifier</dt>
                <dd>+ {{$race->int_mod}} pts.</dd>
                <dt>Charsima Modifier</dt>
                <dd>+ {{$race->chr_mod}} pts.</dd>
                <dt>Accuracy Modifier</dt>
                <dd>+ {{$race->accuracy_mod * 100}} %</dd>
                <dt>Dodge Modifier</dt>
                <dd>+ {{$race->dodge_mod * 100}} %</dd>
                <dt>Looting Modifier</dt>
                <dd>+ {{$race->looting_mod * 100}} %</dd>
                <dt>Defense Modifier</dt>
                <dd>+ {{$race->deffense_mod * 100}} %</dd>
            </dl>
            @if (!is_null(auth()->user()))
                @if (auth()->user()->hasRole('Admin'))
                    <a href="{{route('races.edit', [
                                        'race' => $race
                                    ])}}" class="btn btn-primary mt-2">Edit</a>
                @endif
            @endif
        </x-core.cards.card>
@endsection
