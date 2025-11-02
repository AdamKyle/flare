@php
  $filterKey = $filter->getKey();
@endphp

<div
  x-cloak
  id="{{ $tableName }}-dateRangeFilter-{{ $filterKey }}"
  x-data="flatpickrFilter(
            $wire,
            '{{ $filterKey }}',
            @js($filter->getConfigs()),
            $refs.dateRangeInput,
            '{{ App::currentLocale() }}',
          )"
>
  <x-livewire-tables::tools.filter-label
    :$filter
    :$filterLayout
    :$tableName
    :$isTailwind
    :$isBootstrap4
    :$isBootstrap5
    :$isBootstrap
  />
  <div
    @class([
      'w-full rounded-md shadow-sm text-left ' => $isTailwind,
      'd-inline-block mb-md-0 input-group mb-3 w-100' => $isBootstrap,
    ])
  >
    <input
      type="text"
      x-ref="dateRangeInput"
      x-on:click="init"
      x-on:change="changedValue($refs.dateRangeInput.value)"
      value="{{ $filter->getDateString(isset($this->appliedFilters[$filterKey]) ? $this->appliedFilters[$filterKey] : '') }}"
      wire:key="{{ $filter->generateWireKey($tableName, 'dateRange') }}"
      id="{{ $tableName }}-filter-dateRange-{{ $filterKey }}"
      @class([
        'focus:ring-opacity-50 inline-block w-full rounded-md border-gray-300 align-middle shadow-sm transition duration-150 ease-in-out focus:border-indigo-300 focus:ring focus:ring-indigo-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white' => $isTailwind,
        'd-inline-block form-control w-100' => $isBootstrap,
      ])
      @if($filter->hasConfig('placeholder')) placeholder="{{ $filter->getConfig('placeholder') }}" @endif
    />
  </div>
</div>
