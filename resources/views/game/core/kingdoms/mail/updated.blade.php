@extends('flare.email.core_email', [
    'title'          => 'Kingdoms Have Been Updated',
    'showBottomText' => true,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#637381">
            Hello {{$user->character->name}}, the following are a list of kingdoms that have been updated.
        </mj-text>

        <mj-table>
            <tr style="border-bottom:1px solid #2D2424;text-align:left;padding:15px 0;">
                <th style="padding: 0 15px 0 0;color:#637381;">Kingdom Name</th>
                <th style="padding: 0 0 0 15px;color:#637381;">X Position</th>
                <th style="padding: 0 0 0 15px;color:#637381;">Y Position</th>
                <th style="padding: 0 0 0 15px;color:#637381;">Plane</th>
            </tr>
            @foreach ($kingdomData as $kingdom)
                <tr>
                    <td style="padding: 0 15px 0 0;color:#637381;">{{$kingdom['name']}}</td>
                    <td style="padding: 0 0 0 15px;color:#637381;">{{$kingdom['x_position']}}</td>
                    <td style="padding: 0 0 0 15px;color:#637381;">{{$kingdom['y_position']}}</td>
                    <td style="padding: 0 0 0 15px;color:#637381;">{{$kingdom['plane']}}</td>
                </tr>
            @endforeach
        </mj-table>

        <mj-button background-color="#21A52C" align="center" color="#637381;" font-size="17px" font-weight="bold" href="{{route('login')}}" width="300px">
            Login
        </mj-button>

    </mj-column>
@endsection
