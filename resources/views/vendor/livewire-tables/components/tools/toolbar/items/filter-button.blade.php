@aware(["tableName", "isTailwind", "isBootstrap", "isBootstrap4", "isBootstrap5", "localisationPath"])

@props([])

<div
  @class([
      "ml-md-2 mb-md-0 mb-3 ml-0" => $isBootstrap4,
      "ms-md-2 mb-md-0 ms-0 mb-3" => $isBootstrap5 && $this->searchIsEnabled(),
      "mb-md-0 mb-3" => $isBootstrap5 && ! $this->searchIsEnabled(),
  ])
>
  <div
    @if ($this->isFilterLayoutPopover())
        x-data="{ filterPopoverOpen: false }"
        x-on:keydown.escape.stop="
            if (! this.childElementOpen) {
                filterPopoverOpen = false
            }
        "
        x-on:mousedown.away="
            if (! this.childElementOpen) {
                filterPopoverOpen = false
            }
        "
    @endif
    @class([
        "btn-group d-block d-md-inline" => $isBootstrap,
        "relative block text-left md:inline-block" => $isTailwind,
    ])
  >
    <div>
      <button
        type="button"
        @class([
            "btn dropdown-toggle d-block d-md-inline w-100" => $isBootstrap,
            "focus:ring-opacity-50 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:border-indigo-300 focus:ring focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600" => $isTailwind,
        ])
        @if ($this->isFilterLayoutPopover())
            x-on:click="filterPopoverOpen = !filterPopoverOpen"
            aria-haspopup="true"
            x-bind:aria-expanded="filterPopoverOpen"
            aria-expanded="true"
        @endif
        @if ($this->isFilterLayoutSlideDown()) x-on:click="filtersOpen = !filtersOpen" @endif
      >
        {{ __($localisationPath . "Filters") }}

        @if ($count = $this->getFilterBadgeCount())
          <span
            @class([
                "badge badge-info" => $isBootstrap,
                "ml-1 inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs leading-4 font-medium text-indigo-800 capitalize dark:bg-indigo-200 dark:text-indigo-900" => $isTailwind,
            ])
          >
            {{ $count }}
          </span>
        @endif

        @if ($isTailwind)
          <x-heroicon-o-funnel class="-mr-1 ml-2 h-5 w-5" />
        @else
          <span
            @class([
                "caret" => $isBootstrap,
            ])
          ></span>
        @endif
      </button>
    </div>

    @if ($this->isFilterLayoutPopover())
      <x-livewire-tables::tools.toolbar.items.filter-popover />
    @endif
  </div>
</div>
