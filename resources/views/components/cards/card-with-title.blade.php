@props([
    'title' => 'Example',
])

<h4>{{$title}}</h4>
<div class="card">
    <div class="card-body">
        {{$slot}}
    </div>
</div>