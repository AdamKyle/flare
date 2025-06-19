@props([
    'name',
    'label',
    'model'    => null,
    'modelKey' => null,
    'options'  => [],
])

@php
    $current = old($name, optional($model)->{$modelKey});
    $errorId = $name.'-error';
    $opts     = collect($options)
                  ->map(fn($v,$k)=>['key'=>$k,'label'=>$v])
                  ->values()
                  ->toJson();
    $currentLabel = $options[$current] ?? '';
@endphp

<div
  x-data="{
      open: false,
      search: '{{ $currentLabel }}',
      selected: '{{ $current }}',
      options: {{ $opts }},
      initial: true,
      get filtered() {
        return this.initial
          ? this.options
          : this.options.filter(o =>
              o.label.toLowerCase().includes(this.search.toLowerCase())
            )
      },
      choose(o) {
        this.selected = o.key
        this.search   = o.label
        this.open     = false
      }
    }"
  @click.away="open = false"
  class="mb-5 relative"
>
    <label
      for="combobox-{{ $name }}"
      class="block mb-2 text-sm font-medium text-gray-600 dark:text-gray-300"
    >{{ $label }}</label>

    <select name="{{ $name }}" hidden aria-hidden="true" tabindex="-1">
        <option value="">{{ __('Please select') }}</option>
        @foreach($options as $k=>$v)
            <option value="{{ $k }}" @selected((string)$k===(string)$current)>{{ $v }}</option>
        @endforeach
    </select>

    <input
      id="combobox-{{ $name }}"
      type="text"
      x-model="search"
      @focus="open = true; initial = true"
      @click="open = true; initial = true"
      @input="initial = false"
      @keydown.escape="open = false"
      @keydown.arrow-down.prevent="open = true; $refs.list.querySelector('li')?.focus()"
      role="combobox"
      aria-controls="list-{{ $name }}"
      :aria-expanded="open"
      aria-describedby="{{ $errorId }}"
      @error($name) aria-invalid="true" @enderror
      class="block w-full px-3 py-2 pr-10 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded focus:outline-none focus:ring-2 focus:ring-danube-500 focus:border-danube-500"
      placeholder="{{ __('Search…') }}"
    />

    <i
      class="fas fa-chevron-down pointer-events-none absolute inset-y-0 right-3 my-auto text-gray-500 dark:text-gray-400"
      aria-hidden="true"
    ></i>

    <ul
      x-show="open"
      x-cloak
      x-transition.origin.top
      x-ref="list"
      @keydown.arrow-down.prevent="$event.target.nextElementSibling?.focus()"
      @keydown.arrow-up.prevent="$event.target.previousElementSibling?.focus()"
      @keydown.enter.prevent="choose({ key: $event.target.dataset.key, label: $event.target.innerText })"
      id="list-{{ $name }}"
      role="listbox"
      class="absolute z-10 mt-1 max-h-60 w-full overflow-auto bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded shadow-lg"
    >
        <template x-for="o in filtered" :key="o.key">
            <li
              tabindex="0"
              :data-key="o.key"
              @click="choose(o)"
              role="option"
              :aria-selected="selected===o.key"
              class="cursor-pointer px-3 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700"
              x-text="o.label"
            ></li>
        </template>
        <li
          x-show="filtered.length === 0"
          class="px-3 py-2 text-gray-500 dark:text-gray-400"
        >No results</li>
    </ul>

    @error($name)
    <p id="{{ $errorId }}" class="mt-1 text-sm text-red-600 dark:text-red-400">
        {{ $message }}
    </p>
    @enderror
</div>
