@extends('flare.email.core_email', [
    'title'          => 'Units Recruited',
    'showBottomText' => true,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#dedede">
            Hello {{$user->character->name}}, your units have been recruited.
        </mj-text>

        <mj-text color="#dedede">
            Below are the details of the recruitment process:
        </mj-text>

        <mj-table>
            <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
                <th style="padding: 0 15px 0 0;color:#ffffff;">Kingdom Name</th>
                <th style="padding: 0 0 0 15px;color:#ffffff;">X Position</th>
                <th style="padding: 0 0 0 15px;color:#ffffff;">Y Position</th>
            </tr>
            <tr>
                <td style="padding: 0 15px 0 0;color:#ffffff;">{{$kingdom->name}}</td>
                <td style="padding: 0 0 0 15px;color:#ffffff;">{{$kingdom->x_position}}</td>
                <td style="padding: 0 0 0 15px;color:#ffffff;">{{$kingdom->y_position}}</td>
                <td style="padding: 0 0 0 15px;color:#ffffff;">{{$kingdom->gameMap->name}}</td>
            </tr>
        </mj-table>

        <mj-table>
            <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
              <th style="padding: 0 15px 0 0;color:#ffffff;">Unit Name</th>
              <th style="padding: 0 15px 0 0;color:#ffffff;">Total Units</th>
            </tr>
            <tr>
                <td style="padding: 0 15px 0 0;color:#ffffff;">{{$unit->name}}</td>
                <td style="padding: 0 15px 0 0;color:#ffffff;">{{$amount}}</td>
            </tr>
        </mj-table>

        <mj-button background-color="#388a2d"
                href="{{route('login')}}">
            Login!
        </mj-button>

    </mj-column>
@endsection
