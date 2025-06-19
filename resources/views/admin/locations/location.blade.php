@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
        @php
            $backUrl = route('locations.list');

            if (
                ! auth()
                    ->user()
                    ->hasRole('Admin')
            ) {
                $backUrl = '/information/locations';
            }
        @endphp

        <x-core.cards.card-with-title
            title="{{$location->name}}"
            buttons="true"
            backUrl="{{$backUrl}}"
            editUrl="{{route('location.edit', ['location' => $location->id])}}"
        >
            @include(
                'admin.locations.partials.location',
                [
                    'location' => $location,
                ]
            )
        </x-core.cards.card-with-title>

        @if (! is_null($location->questRewardItem))
            <x-core.cards.card-with-title title="Quest Item">
                <p class="my-2">
                    This location will drop a quest item upon visiting the
                    location.
                </p>
                <dl>
                    <dt>Item Name:</dt>
                    <dd>{{ $location->questRewardItem->name }}</dd>
                    @if (! is_null($usedInQuest))
                        <dt>Used in quest:</dt>
                        <dd>
                            @guest
                                <a
                                    href="{{
                                        route('info.page.quest', [
                                            'quest' => $usedInQuest->id,
                                        ])
                                    }}"
                                    target="_blank"
                                >
                                    <i class="fas fa-external-link-alt"></i>
                                    {{ $usedInQuest->name }}
                                </a>
                            @else
                                @if (auth()->user()->hasRole('Admin'))
                                    <a
                                        href="{{
                                            route('quests.show', [
                                                'quest' => $usedInQuest->id,
                                            ])
                                        }}"
                                        target="_blank"
                                    >
                                        <i class="fas fa-external-link-alt"></i>
                                        {{ $usedInQuest->name }}
                                    </a>
                                @else
                                    <a
                                        href="{{
                                        route('info.page.quest', [
                                            'quest' => $usedInQuest->id,
                                        ])
                                    }}"
                                        target="_blank"
                                    >
                                        <i class="fas fa-external-link-alt"></i>
                                        {{ $usedInQuest->name }}
                                    </a>
                                @endif
                            @endguest
                        </dd>
                    @endif
                </dl>
            </x-core.cards.card-with-title>
        @endif
    </x-core.layout.info-container>
@endsection
