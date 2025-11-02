<p class="mb-4">
  Below you can change the color and style of your cosmetic text. This text will
  then be displayed any time you send a public message.
</p>
<x-core.separator.separator />
<form
  action="{{ route('user.settings.cosmetic-text', ['user' => $user->id]) }}"
  method="POST"
>
  @csrf

  <div class="grid grid-cols-2 gap-4">
    <div class="mb-5">

      <x-form-elements.coloured-select   name="chat_text_color"
                                         label="Chat Color text"
                                         :model="$user"
                                         modelKey="chat_text_color"
                                         :options="[
                                          'ocean-depths' => 'ocean-depths',
                                          'memories-grass' => 'memories-grass',
                                          'depths-despair' => 'depths-despair',
                                          'lipstick' => 'lipstick',
                                          'fifties-cheeks' => 'fifties-cheeks',
                                          'sky-clouds' => 'sky-clouds',
                                          'golden-sheen' => 'golden-sheen',
                                        ]" />

      <x-form-elements.check-box name="chat_is_bold" label="Bold text?" :model="$user" model-key="chat_is_bold" />

      <x-form-elements.check-box name="chat_is_italic" label="Italic text?" :model="$user" model-key="chat_is_italic" />

    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting these options you are stating you want to (optionally) change
      the color of your chat text for public messages and (possibly)
      bold/italicize the text. These options can be reversed by clearing the
      color field and unchecking the boxes.
    </x-core.alerts.info-alert>
  </div>

  <x-core.buttons.primary-button type="submit">
    Save Changes
  </x-core.buttons.primary-button>
</form>
