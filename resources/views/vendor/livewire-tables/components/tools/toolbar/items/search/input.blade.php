@aware(['isTailwind', 'isBootstrap'])
<input
  type="search"
  wire:model{{ $this->getSearchOptions() }}="search"
  placeholder="{{ $this->getSearchPlaceholder() }}"
  aria-label="Search"
  {{
    $attributes
      ->merge($this->getSearchFieldAttributes())
      ->class(
        $isTailwind
          ? ($this->hasSearchIcon
            ? 'block w-full rounded-l-md border border-gray-300 bg-gray-100 py-2 pr-4 pl-10 text-gray-900 placeholder-gray-500 transition duration-150 ease-in-out focus:border-gray-400 focus:ring-2 focus:ring-gray-400 focus:outline-none sm:w-64 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:ring-gray-500'
            : 'block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 transition duration-150 ease-in-out focus:border-gray-400 focus:ring-2 focus:ring-gray-400 focus:outline-none sm:w-64 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:ring-gray-500')
          : 'form-control border-gray-300',
      )
  }}
/>
