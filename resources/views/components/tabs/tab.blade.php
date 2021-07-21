@props([
    'tab'      => '',
    'title'    => '',
    'icon'     => '',
    'selected' => 'false',
    'active'   => 'false',
])

<li class="nav-item">
    <a class="nav-link {{$active === 'true' ? 'active' : ''}}"
       id="pills-{{$tab}}-tab"
       data-toggle="pill"
       href="#pills-{{$tab}}"
       role="tab"
       aria-controls="pills-{{$tab}}"
       aria-selected="{{$selected}}"
    >
        {{$title}}

        @if ($icon !== '')
            <i class="fas fa-exclamation-triangle inventory-set-error"></i>
        @endif
    </a>
</li>
