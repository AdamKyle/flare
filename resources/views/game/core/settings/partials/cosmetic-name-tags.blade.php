<x-core.cards.card-with-title title="Cosmetic Name Tag">
    <p class="mb-4">
        Below you can select a name tag that will be appended to your name in
        chat and seen by all when you talk in public chat. Name tags are a way
        to show off your accomplishments and stand out a bit.
    </p>
    <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
    <form
        action="{{ route('user.settings.cosmetic-name-tag', ['user' => $user->id]) }}"
        method="POST"
    >
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div class="mb-5">
                <label class="label block mb-2" for="name_tag">Name tag</label>
                <select
                    class="form-control"
                    name="name_tag"
                    id="name_tag"
                    value="{{ ! is_null($user) ? $user->chat_text_color : null }}"
                >
                    <option value="">Please select</option>
                    @foreach ($nameTags as $value => $label)
                        <option
                            value="{{ $value }}"
                            {{ $user->name_tag === $value ? 'selected' : '' }}
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By Selecting a name tag, you are stating you want to append a
                name tag to your character name for all to see in chat. This is
                optional and can be removed or changed at any time.
            </x-core.alerts.info-alert>
        </div>

        <x-core.buttons.primary-button type="submit">
            Save Changes
        </x-core.buttons.primary-button>
    </form>
</x-core.cards.card-with-title>
