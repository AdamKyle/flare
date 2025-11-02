<x-core.modals.modal
  id="resetAccount"
  title="Reset Account"
  formId="account-reset"
  formActionTitle="Yes. I am sure"
>
  <x-slot:trigger>
    <x-core.buttons.primary-button type="button" css="w-full">
      Reset Account
    </x-core.buttons.primary-button>
  </x-slot>

  <p class="mb-4">
    Resetting your account, allows you to pick a new race and class. You can
    change your name at the top of Account Settings.
  </p>
  <p class="mb-4">
    Upon resetting your account we delete everythig about your account except
    your user profile. This means you lose all kingdoms, enchanted items,
    currencies, skills, everything. You keep and carry over nothing. You start
    from level one again.
  </p>
  <p class="mb-4">Are you sure you want to do this? There is no going back.</p>

  <h3 class="mb-4 text-sky-600 dark:text-sky-500">
    Optional - Choose new Race and Class.
  </h3>
  <p class="mt-4 mb-4">
    By selecting any of these options your character will be re-rolled with
    them. You can reset your account as many times as you want, however, realise
    you
    <strong>lose a lot of progression when you do that</strong>
    .
  </p>
  <x-core.alerts.info-alert title="ATTN!">
    Please be aware that the Guide quest will be enabled for all characters and
    there no way to disabled them. You will restart your progress in sdaid guide
    quests when you re-roll your character.
  </x-core.alerts.info-alert>
  <p class="mb-4">
    By selecting Enable Guide, we will not show you the initial popup, instead:
    Click the Guide Quest in the top navigation after resetting the account.
  </p>

  <form
    action="{{ route('reset.account', ['user' => $user]) }}"
    id="account-reset"
    method="POST"
  >
    @csrf

    <div class="mb-5">
      <label for="races" class="label mb-2 block">
        {{ __('Choose a Race') }}
      </label>
      <select class="form-control" id="races" name="race">
        <option value="">Please Select (Optional)</option>
        @foreach ($races as $id => $name)
          <option
            value="{{ $id }}"
            {{ (int) old('race') === (int) $id ? 'selected' : '' }}
          >
            {{ $name }}
          </option>
        @endforeach
      </select>

      @error('race')
        <div class="pt-3 text-red-800 dark:text-red-500" role="alert">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>

    <div class="mb-5">
      <label for="classes" class="label mb-2 block">
        {{ __('Choose a class') }}
      </label>
      <select class="form-control" id="classes" name="class">
        <option value="">Please Select (Optional)</option>
        @foreach ($classes as $id => $name)
          <option
            value="{{ $id }}"
            {{ (int) old('class') === (int) $id ? 'selected' : '' }}
          >
            {{ $name }}
          </option>
        @endforeach
      </select>

      @error('class')
        <div class="pt-3 text-red-800 dark:text-red-500" role="alert">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>
  </form>

  <x-slot:footer>
    <button
      type="button"
      x-on:click="modalIsOpen = false"
      class="rounded-sm px-4 py-2 text-center text-sm font-medium tracking-wide whitespace-nowrap text-neutral-600 transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black active:opacity-100 active:outline-offset-0 dark:text-neutral-300 dark:focus-visible:outline-white"
    >
      Cancel
    </button>

    <button
      type="submit"
      form="account-reset"
      class="rounded-sm bg-red-600 px-4 py-2 text-center text-sm font-medium tracking-wide whitespace-nowrap text-white transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 active:opacity-100 active:outline-offset-0 dark:bg-red-600"
    >
      Yes. I am sure
    </button>
  </x-slot>
</x-core.modals.modal>
