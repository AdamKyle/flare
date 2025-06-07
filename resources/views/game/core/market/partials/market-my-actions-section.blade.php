<div class="flex items-center">
    <div class="mr-2">
        <form
            method="post"
            action="{{ route('game.delist.current-listing', ['marketBoard' => $row->id]) }}"
        >
            @csrf
            <x-core.buttons.danger-button type="submit">
                Remove Listing
            </x-core.buttons.danger-button>
        </form>
    </div>
    <div>
        <x-core.buttons.link-buttons.primary-button
            href="{{route('game.edit.current-listings', ['marketBoard' => $row->id])}}"
        >
            Edit Listing
        </x-core.buttons.link-buttons.primary-button>
    </div>
</div>
