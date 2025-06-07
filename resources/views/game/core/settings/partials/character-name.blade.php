<x-core.cards.card-with-title title="Character Name">
    <form
        action="{{ route('user.settings.character', ['user' => $user->id]) }}"
        method="POST"
    >
        @csrf

        <div class="mb-5">
            <label class="label block mb-2" for="character-name">
                Character Name
            </label>
            <input
                id="character-name"
                type="text"
                class="form-control"
                name="name"
                value="{{ $name }}"
                required
                autofocus
            />

            @error('name')
                <div class="invalid-feedback mt-2" role="alert">
                    <strong>{{ $message }}</strong>
                </div>
            @enderror
        </div>

        <x-core.buttons.primary-button type="submit">
            Change name
        </x-core.buttons.primary-button>
    </form>
</x-core.cards.card-with-title>
