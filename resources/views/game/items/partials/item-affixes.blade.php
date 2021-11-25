<div class="row">
    @if (!is_null($item->itemPrefix))
        <div class="{{is_null($item->itemSuffix) ? 'col-md-12' : 'col-md-6'}}">
          <x-core.cards.card>
            <h5 class="mb-2">Prefix</h5>
            <p>{{$item->itemPrefix->description}}</p>
            <hr />
            @include('game.items.partials.item-prefix', ['item' => $item])
          </x-core.cards.card>
        </div>
    @endif
    @if (!is_null($item->itemSuffix))
        <div class="{{is_null($item->itemPrefix) ? 'col-md-12' : 'col-md-6'}}">
            <x-core.cards.card>
              <h5 class="mb-2">Suffix</h5>
              <p>{{$item->itemSuffix->description}}</p>
              <hr />
              @include('game.items.partials.item-suffix', ['item' => $item])
            </x-core.cards.card>
        </div>
    @endif
</div>

