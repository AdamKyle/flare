
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Security Questions">
            <form method="POST" action="{{route('user.settings.security', ['user' => $user])}}">
                @csrf
                
                <div class="row justify-content-center mb-3">
                    <div class="col-md-8">
                        <div class="alert alert-info">
                            You can reset your security questions here by filling out the form and entering your account password.
                            Do not forget these answers as we cannot reset them for you.
                        </div>
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

                <div class="form-group row">
                    <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Account password') }}</label>

                    <div class="col-md-6">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required />

                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <div class="col-md-6 offset-md-4">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Update Security Questions') }}
                        </button>
                    </div>
                </div>
            </form>
        </x-cards.card-with-title>
    </div>
</div>