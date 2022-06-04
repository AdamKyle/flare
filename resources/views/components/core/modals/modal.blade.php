<div id="{{$id}}" class="modal" data-animations="fadeInDown, fadeOutUp">
    <div class="modal-dialog {{isset($largeModal) ? 'max-w-7xl' : 'max-w-2xl'}}">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">{{$title}}</h2>
                <button type="button" class="close la la-times" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{$slot}}
            </div>
            <div class="modal-footer">
                <div class="flex ltr:ml-auto rtl:mr-auto">
                    <div class="mr-4">
                        <x-core.buttons.danger-button data-dismiss="modal">Cancel</x-core.buttons.danger-button>
                    </div>

                    @if (isset($formId))
                        <x-core.buttons.primary-button onclick="event.preventDefault();
                               document.getElementById('character-deletion').submit();">{{$formActionTitle}}</x-core.buttons.primary-button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
