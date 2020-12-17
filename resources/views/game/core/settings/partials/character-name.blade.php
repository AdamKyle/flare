
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Character Name">
            <form action="{{route('user.settings.character', ['user' => $user->id])}}" method="POST">
                @csrf
                
                <div class="form-group row">
                    <label for="name" class="col-md-4 col-form-label text-md-right">{{ __('Character Name') }}</label>

                    <div class="col-md-6">
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{$name}}" required autocomplete="name" autofocus>

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <div class="col-md-6 offset-md-4">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Change Name') }}
                        </button>
                    </div>
                </div>
            </form>
        </x-cards.card-with-title>
    </div>
</div>