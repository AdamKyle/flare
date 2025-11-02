@aware(['isTailwind', 'isBootstrap'])

<div
  @class([
    'd-inline-flex h-100 align-items-center ' => $isBootstrap,
  ])
>
  <div
    wire:click="clearSearch"
    @class([
      'btn btn-outline-secondary d-inline-flex align-items-center h-100' => $isBootstrap,
      'inline-flex h-full cursor-pointer items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600' => $isTailwind,
    ])
  >
    @if ($isTailwind)
      <x-heroicon-m-x-mark class="h-4 w-4" />
    @else
      <x-heroicon-m-x-mark class="laravel-livewire-tables-btn-smaller" />
    @endif
  </div>
</div>
