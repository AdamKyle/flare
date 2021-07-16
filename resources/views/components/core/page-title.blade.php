@props([
    'title' => '',
    'route' => '#',
    'color' => 'primary',
    'link'  => ''
])

<div class="row page-titles">
    <div class="col-md-6 align-self-right">
        <h4 class="mt-2">{!! $title !!}</h4>
    </div>
    <div class="col-md-6 align-self-right">
        <a href="{{$route}}" class="btn btn-{{$color}} float-right ml-2">{{$link}}</a>
        {{$slot}}
    </div>
</div>
