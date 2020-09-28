{{$adventureLog->adventure->name}}

View the log here: {{route('game.current.adventure')}}

Hello {{$character->name}}, some basic adventure information is listed below based on your latest adventure log.
You wen't logged in so we thought we would send you this email with the details below:

Completed: {{$adventureLog->complete ? 'Yes' : 'No'}}

@if (!$adventureLog->complete)
    Reason: You died
@endif

Last level completed: {{$adventureLog->last_completed_level}}
Total adventure levels: {{$adventureLog->adventure->levels}}

Do not reply to this email. This was an automated message. If you want to stop recieveing these you can visit your settings page: and make the appropriate adjustments!</p>
Your email is safe with us, we never use it for anything other then game related information.


