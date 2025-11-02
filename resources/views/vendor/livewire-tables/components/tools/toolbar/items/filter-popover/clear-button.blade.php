@aware(['isTailwind', 'isBootstrap4', 'isBootstrap5', 'localisationPath'])
<button
  type="button"
  wire:click.prevent="setFilterDefaults"
  x-on:click="filterPopoverOpen = false"
  @class([
    'focus:ring-opacity-50 inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm leading-4 font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:border-indigo-300 focus:ring focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:border-gray-500 dark:hover:bg-gray-600' => $isTailwind,
    'dropdown-item btn text-center' => $isBootstrap4,
    'dropdown-item text-center' => $isBootstrap5,
  ])
>
  {{ __($localisationPath . 'Clear') }}
</button>
