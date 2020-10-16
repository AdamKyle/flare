<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="form-wizard">
                    <div class="steps clearfix">
                        <ul>
                            @foreach($steps as $index => $step)
                                <li>
                                    <a href="#">
                                    <span class="circle {{$index === $currentStep ? 'active' : ''}}" wire:click="nextStep({{$index + 1}})">{{$index + 1}}</span>
                                        {{$step}}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="content p-2">
                        @foreach($views as $index => $view)
                            <div class={{$currentStep === $index ? '' : 'hide'}}>
                                @livewire($view, $viewData, key('component-idex-' . $index))
                            </div>
                        @endforeach
                    </div>
                    <div class="p-2 clearfix acions">
                        @if ($currentStep == 0)
                            <button class="btn btn-primary float-right" wire:click="nextStep({{ $currentStep + 1 }})">Next</button>
                        @elseif (count($steps) === $currentStep + 1)
                            <button class="btn btn-primary float-left" wire:click="nextStep({{ $currentStep - 1 }})">Previous</button>
                            <button class="btn btn-success float-right" wire:click="finish({{ $currentStep }})">Finish</button>
                        @elseif ($currentStep < count($steps))
                            <button class="btn btn-primary float-right" wire:click="nextStep({{ $currentStep + 1 }})">Next</button>
                            <button class="btn btn-primary float-left" wire:click="nextStep({{ $currentStep - 1 }})">Previous</button>
                        
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>