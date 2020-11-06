@props([
    'buttonRoute',
    'buttonTitle',
])

<a href="{{$buttonRoute}}" {{$attributes}}>
    {{$buttonTitle}}
</a>