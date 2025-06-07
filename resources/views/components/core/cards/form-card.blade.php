@props([
    'attributes' => '',
    'css' => '',
])

<form class="{{ 'card mt-5 p-5 md:p-10 ' . $css }}" {{ $attributes }}>
    {{ $slot }}
</form>
