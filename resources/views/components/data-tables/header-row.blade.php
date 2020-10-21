@props([
    'headerText',
    'field' => false,
    'sortField' => false,
    'sortBy' => false,
])

<th>
    @if (!$field && !$sortBy && !$sortField)
        {{$headerText}}
    @else
        <a {{$attributes}} href="#">
            {{$headerText}}
            <x-data-tables.header-row-icon field="{{$field}}" sort-field="{{$sortField}}" sort-by="{{$sortBy}}" />
        </a>
    @endif

</th>