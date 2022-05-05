@props([
'attributes' => '',
'css'        => ''
])

<form {{$attributes}}>
    {{$slot}}
</form>
