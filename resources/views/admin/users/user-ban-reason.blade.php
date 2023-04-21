@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Reason For Ban</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-danger float-right ml-2">Cancel</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('ban.user.with.reason', [
                'user' => $user,
            ]) }}">
                @csrf

                <input type="hidden" name="for" value="{{$for}}"/>

                <div class="form-group row">
                    <label for="reason" class="col-md-4 col-form-label text-md-right">{{ __('Reason for ban') }}</label>

                    <div class="col-md-6">
                        <textarea id="reason" type="reason" class="form-control @error('reason') is-invalid @enderror" name="reason" value="{{ $email ?? old('reason') }}" required autofocus></textarea>
                        <small id="reasonHelp" class="form-text text-muted">This reason will be emailed to the user as a reason why.</small>
                        
                        @error('reason')
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
@endsection
