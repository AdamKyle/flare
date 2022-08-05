@include('flare.email.partials.header')
    <p style="margin: 0; font-size: 18px; font-weight: 600; color: #27272a">
        {{$title}}
    </p>
    <p style="font-size: 16px; color: #404040">
        @php
            $kingdom = $building->kingdom;
        @endphp
        The {{$building->name}} for {{$kingdom->name}} at (X/Y): {{$kingdom->x_position}}/{{$kingdom->y_position}} on Plane: {{$kingdom->gameMap->name}} has finished upgrading, and is now level: {{$building->level}}.
    </p>
    <div class="sm-h-8" style="line-height: 24px">&zwnj;</div>
    <a href="https://planesoftlessa.com/login" class="hover-bg-blue-600" style="text-decoration: none; display: inline-block; border-radius: 4px; background-color: #4d7c0f; padding: 20px 24px; font-size: 14px; font-weight: 600; text-transform: uppercase; line-height: 1; color: #fff">
        <!--[if mso]><i style="letter-spacing: 24px; mso-font-width: -100%; mso-text-raise: 26pt;">&nbsp;</i><![endif]-->
        <span style="mso-text-raise: 13pt">Login! &rarr;</span>
        <!--[if mso]><i style="letter-spacing: 24px; mso-font-width: -100%;">&nbsp;</i><![endif]-->
    </a>
@include('flare.email.partials.footer', [
    'user'          => $user,
    'dontShowLogin' => false
])
