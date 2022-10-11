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

    <div class="pb-5">
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

        @if (!is_null($monster->quest_item_id))
            <x-core.cards.card-with-title title="Quest Item Details" buttons="false">
                <h3 class="mt-4 mb-4"><x-item-display-color :item="$monster->questItem" /></h3>
                <p class="mb-4">Quest items are used automatically upon gaining them.</p>
                <p class="mb-4">
                    <strong>Drop Chance: </strong>
                    {{$monster->quest_item_drop_chance * 100}}%
                </p>

                @if (!is_null($quest))
                    <p class="mb-4">
                        Used in: <a href="{{route('info.page.quest', ['quest' =>$quest->id])}}">
                            {{$quest->name}}
                        </a>
                    </p>
                @endif
            </x-core.cards.card-with-title>
            @include('game.items.components.item-details', $questItem)
        @endif
    </div>

</x-core.layout.info-container>






