@props([
    'tab'          => '',
    'title'        => '',
    'icon'         => '',
    'selected'     => 'false',
    'active'       => 'false',
    'navLinkClass' => 'nav-link',
    'iconClass'    => '',
])

<li class="nav-item">
    <a class="nav-link {{$navLinkClass . ' ' . ($active === 'true' ? 'active' : '')}}"
       id="pills-{{$tab}}-tab"
       data-toggle="pill"
       href="#pills-{{$tab}}"
       role="tab"
       aria-controls="pills-{{$tab}}"
       aria-selected="{{$selected}}"
    >
        {{$title}}

        @if ($icon !== '')
            <i class="{{$icon}} {{$iconClass}}"></i>
        @endif
    </a>
</li>
