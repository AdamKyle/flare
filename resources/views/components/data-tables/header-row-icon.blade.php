@props([
    'sortField',
    'sortBy',
    'field',
])

@if ($sortField !== $field)
    <i class="text-muted fas fa-sort"></i>
@elseif ($sortBy === 'asc')
    <i class="fas fa-sort-up"></i>
@else
    <i class="fas fa-sort-down"></i>
@endif