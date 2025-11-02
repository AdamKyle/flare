@aware(['tableName', 'isTailwind', 'isBootstrap', 'isBootstrap4', 'isBootstrap5', 'localisationPath'])
<div
  x-data
  x-cloak
  x-show="reorderStatus"
  @class([
    'mr-md-2 mb-md-0 mr-0 mb-3' => $isBootstrap4,
    'me-md-2 mb-md-0 me-0 mb-3' => $isBootstrap5,
  ])
>
  <button
    x-on:click="reorderToggle"
    type="button"
    @class([
      'btn btn-default d-block d-md-inline' => $isBootstrap,
      'focus:ring-opacity-50 inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition duration-150 ease-in-out hover:text-gray-500 focus:border-indigo-300 focus:ring focus:ring-indigo-200 active:bg-gray-50 active:text-gray-800 md:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600' => $isTailwind,
    ])
  >
    <span x-cloak x-show="currentlyReorderingStatus">
      {{ __($localisationPath . 'cancel') }}
    </span>

    <span x-cloak x-show="!currentlyReorderingStatus">
      {{ __($localisationPath . 'Reorder') }}
    </span>
  </button>

  <div
    :class="{ 'inline d-inline' : currentlyReorderingStatus }"
    x-cloak
    x-show="currentlyReorderingStatus"
  >
    <button
      type="button"
      x-on:click="updateOrderedItems"
      @class([
        'btn btn-default d-block d-md-inline' =>
          $isBootstrap && $this->currentlyReorderingStatus,
        'focus:ring-opacity-50 inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition duration-150 ease-in-out hover:text-gray-500 focus:border-indigo-300 focus:ring focus:ring-indigo-200 active:bg-gray-50 active:text-gray-800 md:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600' => $isTailwind,
      ])
    >
      <span>
        {{ __($localisationPath . 'save') }}
      </span>
    </button>
  </div>
</div>
