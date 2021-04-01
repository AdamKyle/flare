@extends('flare.email.core_email', [
    'title'          => 'Kingdoms Have Been Updated',
    'showBottomText' => true,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#dedede">
            Hello {{$user->character->name}}, the following are a list of kingdoms that have been updated.
        </mj-text>

        <mj-table>
            <tr style="border-bottom:1px solid #ecedee;text-align:left;padding:15px 0;">
              <th style="padding: 0 15px 0 0;color:#ffffff;">Kingdom Name</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">X Position</th>
              <th style="padding: 0 0 0 15px;color:#ffffff;">Y Position</th>
            </tr>
            @foreach ($kingdomData as $kingdom)
                <tr>
                    <td style="padding: 0 15px 0 0;color:#ffffff;">{{$kingdom['name']}}</td>
                    <td style="padding: 0 0 0 15px;color:#ffffff;">{{$kingdom['x_position']}}</td>
                    <td style="padding: 0 0 0 15px;color:#ffffff;">{{$kingdom['y_position']}}</td>
                </tr>
            @endforeach
        </mj-table>

        <mj-button background-color="#388a2d"
                href="{{route('login')}}">
            Login!
        </mj-button>

    </mj-column>
@endsection