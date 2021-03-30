Hello {{$user->character->name}}, the following are a list of kingdoms that have been updated.

@foreach ($kingdomData as $kingdoms)
    Name: {{$kingdoms['name']}}
    X/Y: {{$kingdoms['x_position']}}/{{$kingdoms['y_position']}}
@endforeach

Do not reply to this email. This was an automated message. If you want to stop recieveing these you can visit your settings page: and make the appropriate adjustments!
Your email is safe with us, we never use it for anything other then game related information.