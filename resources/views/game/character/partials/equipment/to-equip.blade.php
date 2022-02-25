<div class="card">
    <div class="card-body">
        <h4 class="card-title">To Equip: <x-item-display-color :item="$itemToEquip" /> </h4>
        <hr />
        @include('game.character.partials.equipment.sections.item-details-to-equip', [
            'item'         => $itemToEquip,
            'details'      => $details,
        ])

        <p class="mt-3 mb-3">
            <sup>*</sup> Attack includes Base Attack Modifier applied automatically, rounded to the nearest whole number.
        </p>
        <p>
            <sup>**</sup> Applies to all skills that increase this modifier.
        </p>
        <p>
            <sup>***</sup> Either voids (Devouring light) or devoids (Devouring Darkness) the enemy. See <a href="/information/voidance" target="_blank">Voidance</a> for more info.
        </p>
        @if ($itemToEquip->can_resurrect)
            <p>
                <sup>rc</sup> Used to determine, upon death in either battle or adventure, if your character can automatically resurrect and heal.
            </p>
        @endif

        @php
            $route = $isShop ? route('game.shop.buy-and-replace', ['character' => $characterId]) : route('game.equip.item', ['character' => $characterId])
        @endphp

        <form class="mt-4" action="{{$route}}" method="POST">
            @csrf

            @if ($bowEquipped || $hammerEquipped)
                <x-core.alerts.warning-alert title="ATTN!" icon="fas fa-exclamation">
                    <p>
                        <strong>Please note</strong>: You already have a two-handed weapon equipped. Equipping this item will replace that item.
                    </p>
                </x-core.alerts.warning-alert>
            @endif

            @include('game.character.partials.equipment.sections.equip.' . ($itemToEquip->type === 'bow' ? 'weapon' : $type), [
                'slotId'      => $slotId,
                'details'     => $details,
                'itemToEquip' => $itemToEquip,
                'type'        => $itemToEquip->type,
                'item'        => $itemToEquip,
                'isShop'      => $isShop,
            ])

            @if ($isShop)
                <button type="submit" class="btn btn-primary">Buy and Replace</button>
            @else
                <button type="submit" class="btn btn-primary">Equip</button>
            @endif
        </form>
    </div>
</div>
