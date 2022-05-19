@push('head')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
@endpush

<div class="mb-5">
    <label class="label block mb-2" for="{{$name}}">{{$label}}</label>
    <input type="hidden" name="{{$name}}" value="{{!is_null($model) ? trim($model->{$modelKey}) : ''}}" id="{{$name}}"/>
    <div id="{{$quillId}}" class="form-control">{!! !is_null($model) ? nl2br($model->{$modelKey}) : '' !!}</div>
</div>

@push('scripts')
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <script>

        window['{{$name}}'] = new Quill('#{{$quillId}}', {
            theme: 'snow'
        });

        window['{{$name}}'].on('text-change', function() {
            document.getElementById('{{$name}}').value = window['{{$name}}'].getText();
        });
    </script>
@endpush
