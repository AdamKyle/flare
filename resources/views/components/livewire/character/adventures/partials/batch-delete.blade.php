@empty ($selected)
@else
    <div class="float-right pb-2">
        <x-forms.button-with-form
            formRoute="{{route('game.adventures.batch-delete')}}"
            formId="{{'delete-adventure-logs'}}"
            buttonTitle="Delete All Selected"
            class="btn btn-primary btn-sm"
        >
            @forelse( $selected as $item)
                <input type="hidden" name="logs[]" value="{{$item}}" />
            @empty
                <input type="hidden" name="logs[]" value="" />
            @endforelse

        </x-forms.button-with-form>
    </div>
@endempty
