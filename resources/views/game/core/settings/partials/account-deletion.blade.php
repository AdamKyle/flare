<div class="my-4 rounded-md border-1 border-gray-500 p-4 dark:border-gray-400">
  <h4 class="text-lg font-bold">Account Management</h4>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="mx-auto w-full md:w-2/3">
    <div class="flex gap-4">
      <div class="flex-1">
        @include('game.core.settings.partials.modals.account-deletion-modal', ['user' => $user])
      </div>
      <div class="flex-1">
        @include('game.core.settings.partials.modals.account-reset-modal', ['user' => $user])
      </div>
    </div>
  </div>
</div>
