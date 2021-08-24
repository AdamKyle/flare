@props([
    'tab'          => '',
    'title'        => '',
    'icon'         => '',
    'selected'     => 'false',
    'active'       => 'false',
    'navLinkClass' => 'nav-link',
    'iconClass'    => '',
    'disabled'     => false,
])

<li class="nav-item">
    <a class="nav-link {{$navLinkClass . ' ' . ($active === 'true' ? 'active' : '')}} {{$disabled ? ' text-muted' : ''}}"
       id="pills-{{$tab}}-tab"
       data-toggle="{{!$disabled ? 'pill' : ''}}"
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
