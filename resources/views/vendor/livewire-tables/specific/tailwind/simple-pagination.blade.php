<div>
  @if ($paginator->hasPages())
    <nav
      role="navigation"
      aria-label="Pagination Navigation"
      class="flex justify-between"
    >
      <span>
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
          <span
            class="relative inline-flex cursor-default items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-500 select-none dark:border-gray-500 dark:bg-gray-700 dark:text-white"
          >
            {!! __('pagination.previous') !!}
          </span>
        @else
          @if (method_exists($paginator, 'getCursorName'))
            {{-- // @todo: Remove `wire:key` once mutation observer has been fixed to detect parameter change for the `setPage()` method call --}}
            <button
              type="button"
              dusk="previousPage"
              wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}"
              wire:click="setPage('{{ $paginator->previousCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
              wire:loading.attr="disabled"
              class="focus:shadow-outline-blue relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-700 transition duration-150 ease-in-out hover:text-gray-500 focus:border-blue-300 focus:outline-none active:bg-gray-100 active:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              {!! __('pagination.previous') !!}
            </button>
          @else
            <button
              type="button"
              wire:click="previousPage('{{ $paginator->getPageName() }}')"
              wire:loading.attr="disabled"
              dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
              class="focus:shadow-outline-blue relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-700 transition duration-150 ease-in-out hover:text-gray-500 focus:border-blue-300 focus:outline-none active:bg-gray-100 active:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              {!! __('pagination.previous') !!}
            </button>
          @endif
        @endif
      </span>

      <span>
        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
          @if (method_exists($paginator, 'getCursorName'))
            {{-- // @todo: Remove `wire:key` once mutation observer has been fixed to detect parameter change for the `setPage()` method call --}}
            <button
              type="button"
              dusk="nextPage"
              wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}"
              wire:click="setPage('{{ $paginator->nextCursor()->encode() }}','{{ $paginator->getCursorName() }}')"
              wire:loading.attr="disabled"
              class="focus:shadow-outline-blue relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-700 transition duration-150 ease-in-out hover:text-gray-500 focus:border-blue-300 focus:outline-none active:bg-gray-100 active:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              {!! __('pagination.next') !!}
            </button>
          @else
            <button
              type="button"
              wire:click="nextPage('{{ $paginator->getPageName() }}')"
              wire:loading.attr="disabled"
              dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
              class="focus:shadow-outline-blue relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-700 transition duration-150 ease-in-out hover:text-gray-500 focus:border-blue-300 focus:outline-none active:bg-gray-100 active:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              {!! __('pagination.next') !!}
            </button>
          @endif
        @else
          <span
            class="relative inline-flex cursor-default items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm leading-5 font-medium text-gray-500 select-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
          >
            {!! __('pagination.next') !!}
          </span>
        @endif
      </span>
    </nav>
  @endif
</div>
