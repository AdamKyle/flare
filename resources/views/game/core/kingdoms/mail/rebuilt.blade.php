@extends('flare.email.core_email', [
    'title'          => 'Rebuilding Finished',
    'showBottomText' => true,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#dedede">
            Hello {{$user->character->name}}, a building: {{$building->name}} has finished being rebuilt.
        </mj-text>

        <mj-text color="#dedede">
            You can see the details below:
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
              <th style="padding: 0 0 0 15px;color:#ffffff;">Current Durability</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">Current Defence</th>
            </tr>
            <tr>
                <td style="padding: 0 15px 0 0;color:#ffffff;">{{$building->name}}</td>
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