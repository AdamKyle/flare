@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="row page-titles">
                <div class="col-md-6 align-self-right">
                    <h4 class="mt-2">Security Check</h4>
                </div>
            </div>

            <div class="alert alert-info">
                Please enter in your security answers.
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('un.ban.request.security', [
                        'user' => $user->id
                    ]) }}">
                        @csrf

                        @foreach($user->securityQuestions as $index => $question)
                            @php $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT); $formatter->format(6) @endphp
                            
                            <div class="form-group row">
                                <label for="{{'answer_' . $formatter->format($index + 1)}}" class="col-md-6 col-form-label text-md-right">{{ $question->question }}</label>

                                <div class="col-md-6">
                                    <input id="{{'answer_' . $formatter->format($index + 1)}}" type="text" class="form-control" name="{{'answer_' . $formatter->format($index + 1)}}" required autofocus>

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <input type="hidden" name="{{'question_' . $formatter->format($index + 1)}}" value="{{$question->question}}" />
                        @endforeach

                        <input type="hidden" name="email" value="{{$user->email}}" />

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Next Step') }}
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
