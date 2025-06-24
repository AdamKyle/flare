@aware(['isTailwind', 'isBootstrap'])
<input
  type="search"
  wire:model{{ $this->getSearchOptions() }}="search"
  placeholder="{{ $this->getSearchPlaceholder() }}"
  aria-label="Search"
  {{ $attributes
      ->merge($this->getSearchFieldAttributes())
      ->class(
        $isTailwind
          ? ($this->hasSearchIcon
              ? 'block w-full sm:w-64 pl-10 pr-4 py-2 text-gray-900 placeholder-gray-500 bg-gray-100 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition duration-150 ease-in-out dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:placeholder-gray-400 dark:focus:ring-gray-500'
              : 'block w-full sm:w-64 px-3 py-2 text-gray-900 placeholder-gray-500 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-400 focus:border-gray-400 transition duration-150 ease-in-out dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:placeholder-gray-400 dark:focus:ring-gray-500'
            )
          : 'form-control border-gray-300'
      )
  }}
/>
