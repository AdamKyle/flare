@extends('flare.email.core_email', [
    'title'          => 'Building Upgraded',
    'showBottomText' => true,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#dedede">
            Hello {{$user->character->name}}, one of your buildings was upgraded.
        </mj-text>

        <mj-text color="#dedede">
            Below are the details of the upgrade process:
        </mj-text>

        <mj-table>
            <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
              <th style="padding: 0 15px 0 0;color:#ffffff;">Kingdom Name</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">X Position</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">Y Position</th>
            </tr>
            <tr>
                <td style="padding: 0 15px 0 0;color:#ffffff;">{{$building->kingdom->name}}</td>
                <td style="padding: 0 0 0 15px;color:#ffffff;">{{$building->kingdom->x_position}}</td>
                <td style="padding: 0 0 0 15px;color:#ffffff;">{{$building->kingdom->y_position}}</td>
            </tr>
        </mj-table>

        <mj-table>
            <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
              <th style="padding: 0 15px 0 0;color:#ffffff;">Building Name</th>
              <th style="padding: 0 15px 0 0;color:#ffffff;">Level</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">Current Durability</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">Current Defence</th>
            </tr>
            <tr>
                <td style="padding: 0 15px 0 0;color:#ffffff;">{{$building->name}}</td>
                <td style="padding: 0 15px 0 0;color:#ffffff;">{{$building->level}}</td>
                <td style="padding: 0 0 0 15px;color:#ffffff;">{{$building->current_durability}}</td>
                <td style="padding: 0 0 0 15px;color:#ffffff;">{{$building->current_defence}}</td>
            </tr>
        </mj-table>

        <mj-button background-color="#388a2d"
                href="{{route('login')}}">
            Login!
        </mj-button>

    </mj-column>
@endsection