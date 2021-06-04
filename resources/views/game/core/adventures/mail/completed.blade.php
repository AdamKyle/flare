@extends('flare.email.core_email', [
    'title'          => $adventureLog->adventure->name,
    'showBottomText' => true,
])

@section('content')
    <mj-column>

        <mj-text font-style="bold"
                font-size="18px"
                color="{{$adventureLog->complete ? '#39AB16' : '#AB161D'}}"
                align="center">
            {{$adventureLog->adventure->name}} Has been {{$adventureLog->complete ? 'completed.' : 'lost.'}}
        </mj-text>

        <mj-text color="#637381">
            Hello {{$character->name}}, some basic adventure information is listed below based on your latest adventure log.
        </mj-text>

        <mj-text color="#637381">
            You weren't logged in so I thought I would send you this email with the details below:
        </mj-text>

        @if (!$adventureLog->complete)
            <mj-text color="#637381">
                Reason for failing: You died (or the adventure took too long). <br />
                If you are dead: You will have to revive in order to embark on the adventure again.
            </mj-text>
        @endif

        <mj-table>
            <tr style="border-bottom:1px solid #2D2424;text-align:left;padding:15px 0;">
              <th style="padding: 0 15px 0 0;color:#637381;">Completed</th>
              <th style="padding: 0 0 0 15px;color:#637381;">Last level Completed</th>
              <th style="padding: 0 0 0 15px;color:#637381;">Total Levels</th>
            </tr>
            <tr>
              <td style="padding: 0 15px 0 0;color:#637381;">{{$adventureLog->complete ? 'Yes' : 'No'}}</td>
              <td style="padding: 0 0 0 15px;color:#637381;">{{$adventureLog->last_completed_level}}</td>
              <td style="padding: 0 0 0 15px;color:#637381;">{{$adventureLog->adventure->levels}}</td>
            </tr>
          </mj-table>

        <mj-button background-color="#388a2d"
                href="{{route('login')}}">
            Login!
        </mj-button>

    </mj-column>
@endsection
