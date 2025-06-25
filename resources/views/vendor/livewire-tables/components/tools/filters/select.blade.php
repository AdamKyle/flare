<div>
  <x-livewire-tables::tools.filter-label
    :$filter
    :$filterLayout
    :$tableName
    :$isTailwind
    :$isBootstrap4
    :$isBootstrap5
    :$isBootstrap
  />

  {{-- make this wrapper relative so we can absolutely position our icon --}}
  <div @class([
        'relative rounded-md shadow-sm inline-block' => $isTailwind,
        'inline-block'                                 => $isBootstrap,
    ])>
    {{-- add appearance-none + extra right padding so the FA icon isn’t clipped --}}
    <select
      {!! $filter->getWireMethod('filterComponents.'.$filter->getKey()) !!}
      {{
          $filterInputAttributes
              ->merge()
              ->class([
                  'appearance-none pr-8 block w-full transition duration-150 ease-in-out rounded-md shadow-sm focus:ring focus:ring-opacity-50' => $isTailwind && ($filterInputAttributes['default-styling'] ?? true),
                  'border-gray-300 focus:border-indigo-300 focus:ring-indigo-200 dark:bg-gray-800 dark:text-white dark:border-gray-600'          => $isTailwind && ($filterInputAttributes['default-colors'] ?? true),
                  'form-control' => $isBootstrap4 && ($filterInputAttributes['default-styling'] ?? true),
                  'form-select'  => $isBootstrap5 && ($filterInputAttributes['default-styling'] ?? true),
                  'p-2 mt-2'
              ])
              ->except(['default-styling','default-colors'])
      }}
    >
      @foreach($filter->getOptions() as $key => $value)
        @if (is_iterable($value))
          <optgroup label="{{ $key }}">
            @foreach ($value as $optionKey => $optionValue)
              <option value="{{ $optionKey }}">{{ $optionValue }}</option>
            @endforeach
          </optgroup>
        @else
          <option value="{{ $key }}">{{ $value }}</option>
        @endif
      @endforeach
    </select>

    {{-- our FontAwesome caret --}}
    <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center top-2">
            <i class="fas fa-chevron-down"></i>
        </span>
  </div>
</div>
