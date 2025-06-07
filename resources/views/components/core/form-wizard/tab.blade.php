@props([
    'target',
    'primaryTitle',
    'secondaryTitle',
    'isActive' => 'false',
])

<button
    class="{{ 'nav-link h5 ' . ($isActive === 'true' ? 'active' : '') }}"
    type="button"
    data-toggle="tab"
    data-target="{{ '#' . $target }}"
>
    {{ $primaryTitle }}
    <small>{{ $secondaryTitle }}</small>
</button>
