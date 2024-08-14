@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>

        <div class="grid md:grid-cols-2 gap-2">
            <x-core.cards.card>
                <i class="fas fa-bug text-red-500 "></i>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-3xl font-bold text-black dark:text-white">
                            {{$bugsCount}}
                        </h4>
                        <a href="admin/feedback/bugs" class="text-sm font-medium text-meta-7">Bugs Count</a>
                    </div>
                    <div class="flex items-center gap-1 text-sm font-medium text-meta-3">
                        <span>0.43%</span>
                        <i class="fas fa-arrow-up text-xs text-meta-3"></i>
                    </div>
                </div>
            </x-core.cards.card>

            <x-core.cards.card>
                <i class="far fa-lightbulb text-yellow-500 dark:text-yellow-400"></i>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-3xl font-bold text-black dark:text-white">
                            {{$suggestionCount}}
                        </h4>
                        <a href="admin/feedback/suggestions" class="text-sm font-medium text-meta-7">Suggestions Count</a>
                    </div>
                    <div class="flex items-center gap-1 text-sm font-medium text-meta-3">
                        <span>0.43%</span>
                        <i class="fas fa-arrow-up text-xs text-meta-3"></i>
                    </div>
                </div>
            </x-core.cards.card>
        </div>

        <div class="my-5">
            <div id="administrator-chat"></div>
        </div>

    </x-core.layout.info-container>
@endsection
