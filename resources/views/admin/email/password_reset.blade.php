@extends('flare.email.core_email', [
    'title'          => 'Request For Password Reset',
    'showBottomText' => false,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#dedede">
            Hello,
        </mj-text>

        <mj-text color="#dedede">
            You recently requested the administrator to reset your password. You may do so below.
        </mj-text>

        <mj-text color="#dedede">
            If you did not personally request this. You may safley ignore it.
        </mj-text>

        <mj-button background-color="#388a2d"
                   href="{{route('password.reset', $token)}}">
            Reset password
        </mj-button>

    </mj-column>
@endsection