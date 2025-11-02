@aware(["tableName", "isTailwind", "isBootstrap", "isBootstrap4", "isBootstrap5", "localisationPath"])
<div
  x-data="{
      open: false,
      childElementOpen: false,
      isTailwind: @js($isTailwind),
      isBootstrap: @js($isBootstrap),
  }"
  x-cloak
  x-show="selectedItems.length > 0 || hideBulkActionsWhenEmpty == false"
  @class([
      "mb-md-0 mb-3" => $isBootstrap,
      "mb-4 w-full md:mb-0 md:w-auto" => $isTailwind,
  ])
>
  <div
    @class([
        "dropdown d-block d-md-inline" => $isBootstrap,
        "relative z-10 inline-block w-full text-left md:w-auto" => $isTailwind,
    ])
  >
    <button
      {{
          $attributes
              ->merge($this->getBulkActionsButtonAttributes)
              ->class([
                  "btn dropdown-toggle d-block d-md-inline" => $isBootstrap && ($this->getBulkActionsButtonAttributes["default-styling"] ?? true),
                  "border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:border-indigo-300 focus:ring-indigo-200 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600" => $isTailwind && ($this->getBulkActionsButtonAttributes["default-colors"] ?? true),
                  "inline-flex justify-center w-full rounded-md border shadow-sm px-4 py-2 text-sm font-medium focus:ring focus:ring-opacity-50" => $isTailwind && ($this->getBulkActionsButtonAttributes["default-styling"] ?? true),
              ])
              ->except(["default", "default-styling", "default-colors"])
      }}
      type="button"
      id="{{ $tableName }}-bulkActionsDropdown"
      @if ($isTailwind)
          x-on:click="open = !open"
      @else
          data-toggle="dropdown"
          data-bs-toggle="dropdown"
      @endif
      aria-haspopup="true"
      aria-expanded="false"
    >
      {{ __($localisationPath . "Bulk Actions") }}

      @if ($isTailwind)
        <x-heroicon-m-chevron-down class="-mr-1 ml-2 h-5 w-5" />
      @endif
    </button>

    @if ($isTailwind)
      <div
        x-on:click.away="
            if (! childElementOpen) {
                open = false
            }
        "
        @keydown.window.escape="if (!childElementOpen) { open = false }"
        x-cloak
        x-show="open"
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="scale-95 transform opacity-0"
        x-transition:enter-end="scale-100 transform opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-start="scale-100 transform opacity-100"
        x-transition:leave-end="scale-95 transform opacity-0"
        class="ring-opacity-5 absolute right-0 z-50 mt-2 w-full origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black focus:outline-none md:w-48"
      >
        <div
          {{
              $attributes
                  ->merge($this->getBulkActionsMenuAttributes)
                  ->class([
                      "bg-white dark:bg-gray-700 dark:text-white" => $isTailwind && ($this->getBulkActionsMenuAttributes["default-colors"] ?? true),
                      "rounded-md shadow-xs" => $isTailwind && ($this->getBulkActionsMenuAttributes["default-styling"] ?? true),
                  ])
                  ->except(["default", "default-styling", "default-colors"])
          }}
        >
          <div class="py-1" role="menu" aria-orientation="vertical">
            @foreach ($this->getBulkActions() as $action => $title)
              <button
                wire:click="{{ $action }}"
                @if ($this->hasConfirmationMessage($action))
                    wire:confirm="{{ $this->getBulkActionConfirmMessage($action) }}"
                @endif
                wire:key="{{ $tableName }}-bulk-action-{{ $action }}"
                type="button"
                role="menuitem"
                {{
                    $attributes
                        ->merge($this->getBulkActionsMenuItemAttributes)
                        ->class([
                            "text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100 focus:text-gray-900 dark:text-white dark:hover:bg-gray-600" => $isTailwind && ($this->getBulkActionsMenuItemAttributes["default-colors"] ?? true),
                            "block w-full px-4 py-2 text-sm leading-5 focus:outline-none flex items-center space-x-2" => $isTailwind && ($this->getBulkActionsMenuItemAttributes["default-styling"] ?? true),
                        ])
                        ->except(["default", "default-styling", "default-colors"])
                }}
              >
                <span>{{ $title }}</span>
              </button>
            @endforeach
          </div>
        </div>
      </div>
    @else
      <div
        {{
            $attributes
                ->merge($this->getBulkActionsMenuAttributes)
                ->class([
                    "dropdown-menu dropdown-menu-right w-100" => $isBootstrap4 && ($this->getBulkActionsMenuAttributes["default-styling"] ?? true),
                    "dropdown-menu dropdown-menu-end w-100" => $isBootstrap5 && ($this->getBulkActionsMenuAttributes["default-styling"] ?? true),
                ])
                ->except(["default", "default-styling", "default-colors"])
        }}
        aria-labelledby="{{ $tableName }}-bulkActionsDropdown"
      >
        @foreach ($this->getBulkActions() as $action => $title)
          <a
            href="#"
            @if ($this->hasConfirmationMessage($action))
                wire:confirm="{{ $this->getBulkActionConfirmMessage($action) }}"
            @endif
            wire:click="{{ $action }}"
            wire:key="{{ $tableName }}-bulk-action-{{ $action }}"
            {{
                $attributes
                    ->merge($this->getBulkActionsMenuItemAttributes)
                    ->class([
                        "dropdown-item" => $isBootstrap && ($this->getBulkActionsMenuItemAttributes["default-styling"] ?? true),
                    ])
                    ->except(["default", "default-styling", "default-colors"])
            }}
          >
            {{ $title }}
          </a>
        @endforeach
      </div>
    @endif
  </div>
</div>
