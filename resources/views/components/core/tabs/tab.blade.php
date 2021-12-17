@props([
    'active' => 'false',
    'id'     => 'default-tab',
    'href'   => '#first',
])

@php
  $cssClass = '';

  if ($active === 'true') {
    $cssClass = 'border-t-sm border-r border-l -mb-px bg-blue-500';
  }

@endphp
<li class="{{"font-medium px-4 py-2 rounded-t-sm text-center " . $cssClass}}">
  <a id="{{$id}}" href="{{$href}}" class="{{$cssClass !== '' ? 'text-white' : ''}}">{{$slot}}</a>
</li>