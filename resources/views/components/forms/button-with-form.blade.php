@props([
    'formRoute',
    'formId',
    'buttonTitle',
    'btnType' => 'primary',
    'formMethod' => 'POST',
])

<a href="{{$formRoute}}" {{$attributes}}
    onclick="event.preventDefault();
    document.getElementById('{{$formId}}').submit();"
>
    {{$buttonTitle}}
</a>

<form id="{{$formId}}" action="{{$formRoute}}" method="{{$formMethod}}" style="display: none;">
    @csrf

    {{$slot}}
</form>