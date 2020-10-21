@props([
    'formRoute',
    'formId',
    'btnType' => 'primary',
    'buttonTitle',
])

<a href="{{$formRoute}}" {{$attributes}}
    onclick="event.preventDefault();
    document.getElementById('{{$formId}}').submit();"
>
    {{$buttonTitle}}
</a>

<form id="{{$formId}}" action="{{$formRoute}}" method="POST" style="display: none;">
    @csrf

    {{$slot}}
</form>