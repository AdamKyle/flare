@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="row page-titles">
                <div class="col-md-6 align-self-right">
                    <h4 class="mt-2">Register</h4>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row justify-content-center mb-3">
                            <div class="col-md-6">
                                <h3>
                                    <span class="header"></span> Security Questions <i class="fas fa-lock ml-2"></i>
                                </h3>
                                <span class="text-muted">All security answers are encrypted.</span>
                                <hr />
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="question_one" class="col-md-4 col-form-label text-md-right">{{ __('Question One') }}</label>

                            <div class="col-md-6">
                                <select name="question_one" id="question_one" class="form-control" required>
                                    <option value="Whats your favourite movie?">Whats your favourite movie?</option>
                                    <option value="Whats the name of the town you grew up in?">Whats the name of the town you grew up in?</option>
                                    <option value="Whats the brand of your car? (Eg, Ford)">Whats the brand of your car? (Eg, Ford)</option>
                                    <option value="Whats the name of your best friends street?">Whats the name of your best friends street?</option>
                                    <option value="Whats the name of your mothers father?">Whats the name of your mothers father?</option>
                                    <option value="Where was the last place you went on vacation?">Where was the last place you went on vacation?</option>
                                </select>

                                @error('question_one')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="answer_one" class="col-md-4 col-form-label text-md-right">{{ __('Answer One') }}</label>

                            <div class="col-md-6">
                                <input id="answer_one" type="text" class="form-control @error('answer_one') is-invalid @enderror" name="answer_one" value="{{ old('answer_one') }}" required autofocus>

                                @error('answer_one')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="question_two" class="col-md-4 col-form-label text-md-right">{{ __('Question Two') }}</label>

                            <div class="col-md-6">
                                <select name="question_two" id="question_two" class="form-control" required>
                                    <option value="Whats your favourite movie?">Whats your favourite movie?</option>
                                    <option value="Whats the name of the town you grew up in?">Whats the name of the town you grew up in?</option>
                                    <option value="Whats the brand of your car? (Eg, Ford)">Whats the brand of your car? (Eg, Ford)</option>
                                    <option value="Whats the name of your best friends street?">Whats the name of your best friends street?</option>
                                    <option value="Whats the name of your mothers father?">Whats the name of your mothers father?</option>
                                    <option value="Where was the last place you went on vacation?">Where was the last place you went on vacation?</option>
                                </select>

                                @error('question_two')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="answer_two" class="col-md-4 col-form-label text-md-right">{{ __('Answer One') }}</label>

                            <div class="col-md-6">
                                <input id="answer_two" type="text" class="form-control @error('answer_two') is-invalid @enderror" name="answer_two" value="{{ old('answer_two') }}" required autofocus>

                                @error('answer_two')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row justify-content-center mb-3">
                            <div class="col-md-6">
                                <h3>
                                    <span class="header"></span> Character Info <i class="ra ra-muscle-up ml-2"></i>
                                </h3>
                                <span class="text-muted">
                                    You can learn more about <a href="/information/races-and-classes">races and classes</a>
                                    in the comprehensive help documentation.
                                </span>
                                <hr />
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">
                                {{ __('Character Name') }}

                                <i class="far fa-question-circle pl-2 cursor"
                                   data-toggle="popover"
                                   title="Character Names"
                                   data-content="Character names may not contain spaces an can only be 15 characters long and only contain letters and numbers (of any case)."
                                ></i>
                            </label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="races" class="col-md-4 col-form-label text-md-right">{{ __('Choose a Race') }}</label>

                            <div class="col-md-6">
                                <select class="form-control" id="races" name="race">
                                    @foreach($races as $id => $name)
                                        <option value={{$id}}>{{$name}}</option>
                                    @endforeach
                                </select>

                                @error('race')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="classes" class="col-md-4 col-form-label text-md-right">{{ __('Choose a class') }}</label>

                            <div class="col-md-6">
                                <select class="form-control" id="classes" name="class">
                                    @foreach($classes as $id => $name)
                                        <option value={{$id}}>{{$name}}</option>
                                    @endforeach
                                </select>

                                @error('class')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
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

@push('scripts')
    <script>
        $(function () {
            $('[data-toggle="popover"]').popover()
        })
    </script>
@endpush

