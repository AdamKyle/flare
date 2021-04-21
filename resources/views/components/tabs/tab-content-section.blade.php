@props([
    'tab'    => '',
    'active' => 'false'
])

<div class="tab-pane fade show {{$active === 'true' ? 'active' : ''}}" id="pills-{{$tab}}" role="tabpanel" aria-labelledby="pills-{{$tab}}-tab">
    {{$slot}}
</div>
