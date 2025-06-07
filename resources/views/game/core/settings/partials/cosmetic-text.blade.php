<x-core.cards.card-with-title title="Cosmetic Text">
    <p class="mb-4">
        Below you can change the color and style of your cosmetic text. This
        text will then be displayed any time you send a public message.
    </p>
    <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
    <form
        action="{{ route('user.settings.cosmetic-text', ['user' => $user->id]) }}"
        method="POST"
    >
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div class="mb-5">
                <label class="label block mb-2" for="chat_text_color">
                    Chat Color text
                </label>
                <select
                    class="form-control"
                    name="chat_text_color"
                    id="chat_text_color"
                    value="{{ ! is_null($user) ? $user->chat_text_color : null }}"
                >
                    <option value="">Please select</option>
                    <option
                        value="ocean-depths"
                        {{ $user->chat_text_color === 'ocean-depths' ? 'selected' : '' }}
                        class="ocean-depths"
                    >
                        Ocean Depths
                    </option>
                    <option
                        value="memeories-grass"
                        {{ $user->chat_text_color === 'memories-grass' ? 'selected' : '' }}
                        class="memories-grass"
                    >
                        Memories Grass
                    </option>
                    <option
                        value="depths-despair"
                        {{ $user->chat_text_color === 'depths-despair' ? 'selected' : '' }}
                        class="depths-despair"
                    >
                        Depths Despair
                    </option>
                    <option
                        value="lipstick"
                        {{ $user->chat_text_color === 'lipstick' ? 'selected' : '' }}
                        class="lipstick"
                    >
                        Lip Stick
                    </option>
                    <option
                        value="fifties-cheeks"
                        {{ $user->chat_text_color === 'fifties-cheeks' ? 'selected' : '' }}
                        class="fifties-cheeks"
                    >
                        Fifties Cheeks
                    </option>
                    <option
                        value="sky-clouds"
                        {{ $user->chat_text_color === 'sky-clouds' ? 'selected' : '' }}
                        class="sky-clouds"
                    >
                        Sky Clouds
                    </option>
                    <option
                        value="golden-sheen"
                        {{ $user->chat_text_color === 'golden-sheen' ? 'selected' : '' }}
                        class="golden-sheen"
                    >
                        Golden Sheen
                    </option>
                </select>

                <label class="custom-checkbox mb-5 mt-4" for="chat_is_bold">
                    <input type="hidden" name="chat_is_bold" value="0" />
                    <input
                        type="checkbox"
                        id="chat_is_bold"
                        name="chat_is_bold"
                        value="1"
                        {{ $user->chat_is_bold ? 'checked' : '' }}
                    />
                    <span></span>
                    <span>Bold Text?</span>
                </label>

                <label class="custom-checkbox mb-5" for="chat_is_italic">
                    <input type="hidden" name="chat_is_italic" value="0" />
                    <input
                        type="checkbox"
                        id="chat_is_italic"
                        name="chat_is_italic"
                        value="1"
                        {{ $user->chat_is_italic ? 'checked' : '' }}
                    />
                    <span></span>
                    <span>Italicize Text?</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By selecting these options you are stating you want to
                (optionally) change the color of your chat text for public
                messages and (possibly) bold/italicize the text. These options
                can be reversed by clearing the color field and unchecking the
                boxes.
            </x-core.alerts.info-alert>
        </div>

        <x-core.buttons.primary-button type="submit">
            Save Changes
        </x-core.buttons.primary-button>
    </form>
</x-core.cards.card-with-title>
