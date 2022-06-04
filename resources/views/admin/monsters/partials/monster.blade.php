<x-core.layout.info-container>
    @php
        $backUrl = route('monsters.list');

        if (is_null(auth()->user())) {
            $backUrl = '/information/monsters';
        } else {
            if (!auth()->user()->hasRole('Admin')) {
                $backUrl = '/information/monsters';
            }
        }

    @endphp

    <x-core.page-title
        title="{{$monster->name}}"
        route="{{$backUrl}}"
        color="success" link="Back"
    >
        @auth
            @if (auth()->user()->hasRole('Admin'))
                <x-core.buttons.link-buttons.primary-button
                    href="{{route('monster.edit', ['monster' => $monster->id])}}"
                    css="tw-ml-2"
                >
                    Edit Monster
                </x-core.buttons.link-buttons.primary-button>
            @endif
        @endauth
    </x-core.page-title>

    @include('admin.monsters.partials.details', [
        'monster' => $monster,
        'quest'   => $quest,
        'canEdit' => true,
    ])

    <div class="mt-4">
        <h3 class="mb-4">Quest Item Details</h3>
        @if (!is_null($monster->quest_item_id))
            <p class="mb-4">
                <strong>Drop Chance: </strong>
                {{$monster->quest_item_drop_chance * 100}}%, used in:
                <a href="{{route('info.page.quest', ['quest' =>$quest->id])}}">
                    {{$quest->name}}
                </a>
            </p>
            @include('game.items.components.item-details', $questItem)
        @endif
    </div>

</x-core.layout.info-container>






