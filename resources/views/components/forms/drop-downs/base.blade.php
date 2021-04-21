@props([
    'floatLeft' => 'false',
    'btnType'   => 'primary',
    'btnSize'   => '',
    'dropDownId',
    'dropDownTitle'
])

<div class="dropdown show {{$floatLeft === 'true' ? 'float-left' : ''}} mr-2">
    <a class="btn btn-{{$btnType}} {{$btnSize}} dropdown-toggle" href="#" role="button" id="{{$dropDownId}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        {{$dropDownTitle}}
    </a>

    <div class="dropdown-menu" aria-labelledby="{{$dropDownId}}">

        {{$slot}}

    </div>
</div>
