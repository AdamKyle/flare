@extends('flare.email.core_email', [
    'title'          => 'Request to be unbanned',
    'showBottomText' => false,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#dedede">
            Hello Administrator, {{$user->character->name}} is requesting to be unbanned.
        </mj-text>

        <mj-text color="#dedede">
            Original Reason:
        </mj-text>

        <mj-text color="#dedede">
            {{$user->banned_reason}}
        </mj-text>

        <mj-button background-color="#388a2d"
                   href="{{route('login')}}">
            Login
        </mj-button>

    </mj-column>
@endsection