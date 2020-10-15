Reason for email: {{$genericSubject}}

@if ($user->hasRole('Admin'))
    Hello, Administrator
@else
    Hello, {{$user->character->name}}
@endif


{{$genericMessage}}

@if (!$dontShowLogin)
    Login {{route('login')}} and see your the new changes!
@endif

Do not reply to this email. This was an automated message.
Your email is safe with us, we never use it for anything other then game related information.
