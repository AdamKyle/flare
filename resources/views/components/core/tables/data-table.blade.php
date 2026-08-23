@props([
    'paginator',
    'columns',
    'emptyMessage' => 'No records found.',
    'searchable' => false,
    'filters' => [],
])

<div class="space-y-4">
    @if ($searchable || count($filters) > 0)
        <form method="GET" class="flex flex-wrap items-end gap-2">
            @foreach (request()->except(array_merge(['search', 'page'], array_column($filters, 'field'))) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $arrayValue)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}" />
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
                @endif
            @endforeach

            @if ($searchable)
                <div>
                    <label for="table-search" class="sr-only">Search</label>
                    <input
                        type="text"
                        id="table-search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search..."
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    />
                </div>
            @endif

            @foreach ($filters as $filter)
                <div>
                    <label for="table-filter-{{ $filter['field'] }}" class="sr-only"> {{ $filter['label'] }} </label>
                    <select
                        id="table-filter-{{ $filter['field'] }}"
                        name="{{ $filter['field'] }}"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    >
                        @foreach ($filter['options'] as $optionValue => $optionLabel)
                            <option
                                value="{{ $optionValue }}"
                                @selected(request($filter['field']) === (string) $optionValue)
                            >
                                {{ $optionLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <button
                type="submit"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
            >
                Search
            </button>
        </form>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    @foreach ($columns as $column)
                        <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">
                            @if ($column->sortable)
                                @php
                                    $isActiveSort = request('sort') === $column->field;
                                    $nextDirection = $isActiveSort && request('direction') !== 'desc' ? 'desc' : 'asc';
                                @endphp
                                <a
                                    href="{{ request()->fullUrlWithQuery(['sort' => $column->field, 'direction' => $nextDirection]) }}"
                                    class="inline-flex items-center gap-1 hover:underline"
                                >
                                    {{ $column->label }}
                                    @if ($isActiveSort)
                                        <i
                                            class="fas fa-sort-{{ request('direction') === 'desc' ? 'down' : 'up' }}"
                                            aria-hidden="true"
                                        ></i>
                                    @endif
                                </a>
                            @else
                                {{ $column->label }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($paginator as $row)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        @foreach ($columns as $column)
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                {!! $column->renderedValue($row) !!}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="{{ count($columns) }}"
                            class="px-3 py-6 text-center text-gray-500 dark:text-gray-400"
                        >
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $paginator->links() }}
</div>
