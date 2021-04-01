@extends('flare.email.core_email', [
    'title'          => $adventureLog->adventure->name,
    'showBottomText' => true,
])

@section('content')
    <mj-column width="400px">

        <mj-text font-style="bold"
                font-size="16px"
                font-family="Helvetica Neue"
                color="#ffffff"
                align="center">
            {{$adventureLog->adventure->name}} Has been {{$adventureLog->complete ? 'completed.' : 'lost.'}}
        </mj-text>

        <mj-text color="#dedede">
            Hello {{$character->name}}, some basic adventure information is listed below based on your latest adventure log.
        </mj-text>

        <mj-text color="#dedede">
            You weren't logged in so we thought we would send you this email with the details below:
        </mj-text>

        @if (!$adventureLog->complete)
            <mj-text color="#dedede">
                Reason for failing: You died. You will have to revive in order to emabrk on the adventure again.
            </mj-text>
        @endif

        <mj-table>
            <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
              <th style="padding: 0 15px 0 0;color:#ffffff;">Completed</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">Last level Completed</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">Total Levels</th>
            </tr>
            <tr>
              <td style="padding: 0 15px 0 0;color:#ffffff;">{{$adventureLog->complete ? 'Yes' : 'No'}}</td>
              <td style="padding: 0 0 0 15px;color:#ffffff;">{{$adventureLog->last_completed_level}}</td>
              <td style="padding: 0 0 0 15px;color:#ffffff;">{{$adventureLog->adventure->levels}}</td>
            </tr>
          </mj-table>

        <mj-button background-color="#388a2d"
                href="{{route('login')}}">
            Login!
        </mj-button>

    </mj-column>
@endsection
