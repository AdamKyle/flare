<div>
  @if ($paginator->hasPages())
    @php(
        isset($this->numberOfPaginatorsRendered[$paginator->getPageName()])
            ? $this->numberOfPaginatorsRendered[$paginator->getPageName()]++
            : $this->numberOfPaginatorsRendered[$paginator->getPageName()] = 1
    )
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between w-full px-4 sm:px-0">
      {{-- Mobile / Small Screens --}}
      <div class="w-full flex justify-between items-center mb-4 sm:hidden">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
          <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600" aria-disabled="true">
                    {!! __('pagination.previous') !!}
                </span>
        @else
          <button
            type="button"
            wire:click="previousPage('{{ $paginator->getPageName() }}')"
            wire:loading.attr="disabled"
            dusk="previousPage.before"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 transition dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
            aria-label="{{ __('pagination.previous') }}"
          >
            {!! __('pagination.previous') !!}
          </button>
        @endif

        {{-- Next --}}
        @if ($paginator->hasMorePages())
          <button
            type="button"
            wire:click="nextPage('{{ $paginator->getPageName() }}')"
            wire:loading.attr="disabled"
            dusk="nextPage.before"
            class="inline-flex items-center px-4 py=2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 transition dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
            aria-label="{{ __('pagination.next') }}"
          >
            {!! __('pagination.next') !!}
          </button>
        @else
          <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600" aria-disabled="true">
                    {!! __('pagination.next') !!}
                </span>
        @endif
      </div>

      {{-- Desktop / Larger Screens --}}
      <div class="hidden sm:flex items-center space-x-2">
        {{-- Previous Arrow --}}
        @if ($paginator->onFirstPage())
          <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <span class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-gray-100 border border-gray-300 rounded-l-md dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </span>
        @else
          <button
            type="button"
            wire:click="previousPage('{{ $paginator->getPageName() }}')"
            dusk="previousPage.after"
            rel="prev"
            class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 transition dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
            aria-label="{{ __('pagination.previous') }}"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
        @endif

        {{-- Page Numbers --}}
        <div class="inline-flex overflow-hidden rounded-md shadow-sm">
          @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
              <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 border border-gray-300 cursor-default leading-5 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600" aria-disabled="true">
                            {{ $element }}
                        </span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
              @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                  <span aria-current="page" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 bg-gray-600">
                                    {{ $page }}
                                </span>
                @else
                  <button
                    type="button"
                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition dark:bg-gray-700 dark:text-gray-200"
                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                  >
                    {{ $page }}
                  </button>
                @endif
              @endforeach
            @endif
          @endforeach
        </div>

        {{-- Next Arrow --}}
        @if ($paginator->hasMorePages())
          <button
            type="button"
            wire:click="nextPage('{{ $paginator->getPageName() }}')"
            dusk="nextPage.after"
            rel="next"
            class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 transition dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
            aria-label="{{ __('pagination.next') }}"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
        @else
          <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <span class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-gray-100 border border-gray-300 cursor-default leading-5 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600" aria-hidden="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </span>
        @endif
      </div>
    </nav>
  @endif
</div>
