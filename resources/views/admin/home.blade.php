@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>

        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <x-core.cards.card>
                <i class="fas fa-bug text-red-500"></i>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-3xl font-bold text-black dark:text-white">
                            {{$bugsCount}}
                        </h4>
                        <a href="admin/feedback/bugs" class="text-sm font-medium text-meta-7">Bugs Count</a>
                    </div>
                    <div class="text-right text-sm font-medium text-meta-3">
                        <span>{{ $bugsPercentage }}%</span>
                        <div class="text-xs text-gray-500">Last 7 days share</div>
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
                    <div class="text-right text-sm font-medium text-meta-3">
                        <span>{{ $suggestionPercentage }}%</span>
                        <div class="text-xs text-gray-500">Last 7 days share</div>
                    </div>
                </div>
            </x-core.cards.card>

            <x-core.cards.card>
                <i class="fas fa-layer-group text-blue-500"></i>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-3xl font-bold text-black dark:text-white">
                                {{ $rewardQueueSummary['queued'] }}
                            </h4>
                            <a href="{{ route('admin.character-reward-queue') }}" class="text-sm font-medium text-meta-7">
                                Character Reward Queue
                            </a>
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ $rewardQueueSummary['processing'] }} processing
                        </span>
                    </div>
                    <div class="mt-3 flex h-10 items-end gap-1 overflow-hidden" aria-label="Reward requests created in the last hour">
                        @forelse ($rewardQueueLastHour as $point)
                            @php
                                $volume = $point['pending'] + $point['processing'] + $point['completed'] + $point['failed'];
                            @endphp
                            <span
                                class="min-w-[2px] flex-1 rounded-t bg-blue-500"
                                style="height: {{ max(4, min(40, $volume * 4)) }}px"
                                title="{{ $point['period'] }}: {{ $volume }}"
                            ></span>
                        @empty
                            <span class="text-xs text-gray-500">No requests in the last hour.</span>
                        @endforelse
                    </div>
                </div>
            </x-core.cards.card>

            <x-core.cards.card>
                <i class="fas fa-map-marked-alt text-green-500"></i>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-3xl font-bold text-black dark:text-white">
                            {{ $exploringCount }}
                        </h4>
                        <a href="{{ route('admin.monitoring.exploration') }}" class="text-sm font-medium text-meta-7">
                            Characters Exploring
                        </a>
                    </div>
                </div>
            </x-core.cards.card>

            <x-core.cards.card>
                <i class="fas fa-handshake text-purple-500"></i>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-3xl font-bold text-black dark:text-white">
                            {{ $factionLoyaltyCount }}
                        </h4>
                        <a href="{{ route('admin.monitoring.faction-loyalty') }}" class="text-sm font-medium text-meta-7">
                            Characters in Faction Loyalty
                        </a>
                    </div>
                </div>
            </x-core.cards.card>

            <x-core.cards.card>
                <i class="fas fa-dungeon text-orange-500"></i>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-3xl font-bold text-black dark:text-white">
                            {{ $delveCount }}
                        </h4>
                        <a href="{{ route('admin.monitoring.delve') }}" class="text-sm font-medium text-meta-7">
                            Characters in Delve
                        </a>
                    </div>
                </div>
            </x-core.cards.card>
            <x-core.cards.card>
                <i class="fas fa-file-alt text-gray-500 dark:text-gray-400"></i>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-semibold text-black dark:text-white">Application Logs</h4>
                        <a href="{{ route('admin.monitoring.logs') }}" class="text-sm font-medium text-meta-7">
                            View Logs Dashboard
                        </a>
                    </div>
                </div>
            </x-core.cards.card>
        </div>

        <div class="my-5">
            <div id="administrator-chat"></div>
        </div>

    </x-core.layout.info-container>
@endsection
