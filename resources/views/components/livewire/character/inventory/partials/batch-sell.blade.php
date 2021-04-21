@if ($batchSell)
    @empty ($selected)
    @else
        <div class="float-right pb-2">
            <x-forms.button-with-form
                formRoute="{{route('game.shop.sell.bulk', ['character' => $character->id])}}"
                formId="{{'shop-sell-form-item-in-bulk'}}"
                buttonTitle="Sell All Selected"
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
