<p class="mb-4">
  Below you can select a new race, which will slightly modify your base,
  unmodified stats and slightly change your skill bonus attributes.
</p>
<div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>
<form
  action="{{ route('user.settings.cosmetic-race-changer', ['user' => $user->id]) }}"
  method="POST"
>
  @csrf

  <div class="grid grid-cols-2 gap-4">
    <div class="mb-5">
      <label class="label mb-2 block" for="race_id">Choose a new race</label>
      <select
        class="form-control"
        name="race_id"
        id="race_id"
        value="{{ ! is_null($user) ? $user->chat_text_color : null }}"
      >
        <option value="">Please select</option>
        @foreach ($races as $value => $label)
          <option
            value="{{ $value }}"
            {{ $user->character->game_race_id === $value ? 'selected' : '' }}
          >
            {{ $label }}
          </option>
        @endforeach
      </select>
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
