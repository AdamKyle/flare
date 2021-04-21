@if ($batchSell)
    @empty ($selected)
    @else
        <div class="float-right pb-2">
            <x-forms.button-with-form
                form-route="{{route('game.shop.sell.bulk', ['character' => $character->id])}}"
                form-id="{{'shop-sell-form-item-in-bulk'}}"
                button-title="Sell All Selected"
                class="btn btn-primary btn-sm"
            >
                @forelse( $selected as $item)
                    <input type="hidden" name="slots[]" value="{{$item}}" />
                @empty
                    <input type="hidden" name="slots[]" value="" />
                @endforelse

            </x-forms.button-with-form>
        </div>
    @endempty
@endif
