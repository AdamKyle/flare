<p class="mb-4">
  Below you can select a name tag that will be appended to your name in chat and
  seen by all when you talk in public chat. Name tags are a way to show off your
  accomplishments and stand out a bit.
</p>
<x-core.separator.separator />
<form
  action="{{ route('user.settings.cosmetic-name-tag', ['user' => $user->id]) }}"
  method="POST"
>
  @csrf

  <div class="grid grid-cols-2 gap-4">
    <div class="mb-5">

      <x-form-elements.select name="name_tag" label="Name Tag" :model="$user" model-key="name_tag" :options="$nameTags" />

    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By Selecting a name tag, you are stating you want to append a name tag to
      your character name for all to see in chat. This is optional and can be
      removed or changed at any time.
    </x-core.alerts.info-alert>
  </div>

  <x-core.buttons.primary-button type="submit">
    Save Changes
  </x-core.buttons.primary-button>
</form>
