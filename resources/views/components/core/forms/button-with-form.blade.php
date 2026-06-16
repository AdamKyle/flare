@props([
    'formRoute' => '',
    'buttonTitle' => ''
])

<form action="{{$formRoute}}" method="POST" {{ $attributes->class('inline-flex items-center') }}>
    @csrf

    <x-core.buttons.primary-button type="submit">
        {{$buttonTitle}}
    </x-core.buttons.primary-button>
</form>
