@extends('layouts.app')

@section('content')
<div class="container small-container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="row page-titles">
                <div class="col-md-6 align-self-right">
                    <h4 class="mt-2">Unban Request</h4>
                </div>
            </div>

            <div class="alert alert-info">
                Please tell us why you think you were banned unfairly. You're request will be responded to with in 72 hours of recieiving it. All future requests will be ignored. <strong>All decisions are final</strong>.
            </div>

            <div class="mb-2 mt-2">
                <strong>Reason you were banned:</strong> {{$user->banned_reason}}
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('un.ban.request.submit', [
                        'user' => $user,
                    ]) }}">
                        @csrf

                        <div class="form-group row">
                            <label for="unban_message" class="col-md-4 col-form-label text-md-right">{{ __('Request To Unban') }}</label>

                            <div class="col-md-6">
                                <textarea id="unban_message" type="unban_message" class="form-control @error('unban_message') is-invalid @enderror" name="unban_message" value="{{ $email ?? old('unban_message') }}" required autofocus></textarea>

                                @error('unban_message')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Submit Request') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
