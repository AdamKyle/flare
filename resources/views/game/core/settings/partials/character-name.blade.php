<div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
  <h4 class="text-lg font-bold">Character Name</h4>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <form
  action="{{ route('user.settings.character', ['user' => $user->id]) }}"
  method="POST"
  class="w-full md:w-2/3"
>
  @csrf

  <div class="mb-5">
    <h3>Change your name</h3>
    <x-form-elements.input name="name"  label="Character Name" required autofocus />

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
</div>
