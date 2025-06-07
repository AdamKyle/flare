@include('flare.email.partials.header')
<p style="margin: 0; font-size: 18px; font-weight: 600; color: #27272a">
    Password Reset Requested
</p>
<p style="font-size: 16px; color: #404040">
    Hello, You recently requested the administrator to reset your password. You
    may do so below.
</p>
<div class="sm-h-8" style="line-height: 24px">&zwnj;</div>
<a
    href="{{ route('password.reset', $token) }}"
    class="hover-bg-blue-600"
    style="
        text-decoration: none;
        display: inline-block;
        border-radius: 4px;
        background-color: #4d7c0f;
        padding: 20px 24px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        line-height: 1;
        color: #fff;
    "
>
    <!--[if mso]>
        <i
            style="
                letter-spacing: 24px;
                mso-font-width: -100%;
                mso-text-raise: 26pt;
            "
        >
            &nbsp;
        </i>
    <![endif]-->
    <span style="mso-text-raise: 13pt">Reset password! &rarr;</span>
    <!--[if mso]>
        <i style="letter-spacing: 24px; mso-font-width: -100%">&nbsp;</i>
    <![endif]-->
</a>
@include(
    'flare.email.partials.footer',
    [
        'user' => null,
        'dontShowLogin' => true,
    ]
)
