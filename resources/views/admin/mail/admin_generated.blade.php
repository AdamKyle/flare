@extends('flare.email.core_email', [
    'title'          => 'Admin Account Created',
    'showBottomText' => false,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#dedede">
            Hello. Your admin account has been created. Please reset your password before logging in.
        </mj-text>

        <mj-button background-color="#388a2d"
                href="{{route('password.reset', $token)}}">
            Reset password
        </mj-button>

    </mj-column>
@endsection
