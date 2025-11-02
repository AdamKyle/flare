<div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
  <h4 class="text-lg font-bold">Account Management</h4>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="w-full md:w-2/3 mx-auto">
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
