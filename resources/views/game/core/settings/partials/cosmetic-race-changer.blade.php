<p class="mb-4">
  Below you can select a new race, which will slightly modify your base,
  unmodified stats and slightly change your skill bonus attributes.
</p>
<x-core.separator.separator />
<form
  action="{{ route('user.settings.cosmetic-race-changer', ['user' => $user->id]) }}"
  method="POST"
>
  @csrf

  <div class="grid grid-cols-2 gap-4">
    <div class="mb-5">

      <x-form-elements.select name="race_id" label="Choose a new race" :model="$user" model-key="race_id" :options="$races" />

    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting a new race, you will switch your current race to the race you
      selected. Your base stats and skill mofiers will slightly update to that
      new races modifiers. You can see more about
      <a href="/information/races-and-classes">races and classes here</a>
      .
    </x-core.alerts.info-alert>
  </div>

  <x-core.buttons.primary-button type="submit">
    Save Changes
  </x-core.buttons.primary-button>
</form>
