<x-core.modals.modal
  id="accountDeletion"
  title="Delete Account"
>
  <x-slot:trigger>
    <x-core.buttons.danger-button type="button" css="w-full">
      Delete Account
    </x-core.buttons.danger-button>
  </x-slot:trigger>

  <p class="mb-4">
    I am very sad to see you go, but I understand. If you are sure this is what
    you want to do, please know the following:
  </p>

  <ul class="mb-4 ml-[20px] list-disc">
    <li>All details about you will be deleted, including: Email and Password</li>
    <li>All kingdoms will be given to the NPC who holds kingdoms.</li>
    <li>All market listings will be deleted.</li>
  </ul>

  <p class="mb-4">
    You will also receive one last email confirming that we have cleaned up your
    account.
  </p>

  <p class="mb-4">
    Are you sure you want to do this? There is no going back, accept to create a
    new account.
  </p>

  <x-core.alerts.info-alert title="One second before you go ...">
    Deleting your account to start over? Click Reset Account instead. You won't
    lose your account.
  </x-core.alerts.info-alert>

  <form
    action="{{ route('delete.account', ['user' => $user]) }}"
    id="character-deletion"
    method="POST"
  >
    @csrf
  </form>

  <x-slot:footer>
    <button
      type="button"
      x-on:click="modalIsOpen = false"
      class="whitespace-nowrap rounded-sm px-4 py-2 text-center text-sm font-medium tracking-wide text-neutral-600 transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black active:opacity-100 active:outline-offset-0 dark:text-neutral-300 dark:focus-visible:outline-white"
    >
      Cancel
    </button>

    <button
      type="submit"
      form="character-deletion"
      class="whitespace-nowrap rounded-sm bg-black border border-black dark:border-white px-4 py-2 text-center text-sm font-medium tracking-wide text-neutral-100 transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black active:opacity-100 active:outline-offset-0 dark:bg-white dark:text-black dark:focus-visible:outline-white"
    >
      Yes. I am sure
    </button>
  </x-slot:footer>
</x-core.modals.modal>
