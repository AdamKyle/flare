@extends('layouts.app')

@section('content')
    <x-core.page-title-slot
        route="{{route('game.current-listings', [
            'character' => auth()->user()->character->id
        ])}}"
        link="Back"
        color="success"
    >
        <x-item-display-color :item="$marketBoard->item" />
    </x-core.page-title-slot>
    <div class="row">
        <div class="col-md-12">
            <form class="mb-3 mb-2" method="POST" action="{{route('game.update.current-listing', [
                'marketBoard' => $marketBoard->id
            ])}}">
                @csrf

                <div class="alert alert-warning">
                    <p>While you are editing your items listed price, your item will not appear on the market board. Once you are done, the item will be relisted.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="market-listed-price">Sell For: </label>
                            <input type="number" class="form-control required" id="market-listed-price" name="listed_price" value={{$marketBoard->listed_price}}>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check move-down-30">
                            <input id="isLocked" class="form-check-input" type="checkbox" data-toggle="toggle" name="is_locked" disabled {{$marketBoard->is_locked ? 'checked' : ''}}>
                            <label for="isLocked" class="form-check-label ml-2">Currently Locked</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Update Pricing</button>
                    </div>
                </div>
            </form>
            <hr />
            <div class="container small-container">
                <x-tabs.pill-tabs-container>
                    <x-tabs.tab tab="base-details" title="Base Information" selected="true" active="true" />
                    @if ($marketBoard->item->usable)
                        <x-tabs.tab tab="usability-stats" title="Usability Details" selected="false" active="false" />
                    @else
                        <x-tabs.tab tab="equip-stats" title="Equip Stats" selected="false" active="false" />
                        <x-tabs.tab tab="attached-affixes" title="Attached Affixes" selected="false" active="false" />
                    @endif
                    <x-tabs.tab tab="market-listings" title="Market Listings" selected="false" active="false" />
                </x-tabs.pill-tabs-container>
                <x-tabs.tab-content>
                    <x-tabs.tab-content-section tab="base-details" active="true">
                        <x-cards.card>
                            @include('game.items.partials.item-details', ['item' => $marketBoard->item])
                        </x-cards.card>
                    </x-tabs.tab-content-section>
                    @if ($marketBoard->item->usable)
                        <x-tabs.tab-content-section tab="usability-stats" active="false">
                            @include('game.items.partials.item-usable-section', [
                                'item'   => $marketBoard->item,
                                'skills' => $skills,
                                'skill'  => $skill,
                            ])
                        </x-tabs.tab-content-section>
                    @else
                        <x-tabs.tab-content-section tab="equip-stats" active="false">
                            <x-cards.card>
                                <div class="alert alert-info mt-2 mb-3">
                                    Values include attached affixes.
                                </div>
                                @include('game.core.partials.equip.details.item-stat-details', ['item' => $marketBoard->item])
                            </x-cards.card>
                        </x-tabs.tab-content-section>
                        <x-tabs.tab-content-section tab="attached-affixes" active="false">
                            @include('game.items.partials.item-affixes', ['item' => $marketBoard->item])
                        </x-tabs.tab-content-section>
                    @endif
                    <x-tabs.tab-content-section tab="market-listings" active="false">
                        <div class="alert alert-info mt-2 mb-3">
                            <p>Market information is live, you cannot click on the rows in the table. This only shows items who's
                                names (including affixes) match that of the item you are trying to sell.</p>
                            <p>
                                If there is no history, or no items (or both) then there is no listing for this item, or the item has never been bought,
                                thus no market history.
                            </p>
                        </div>

                        <div id="market-item-board-{{$marketBoard->item->id}}" data-item-id="{{$marketBoard->item->id}}"></div>
                    </x-tabs.tab-content-section>
                </x-tabs.tab-content>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        renderBoard('market-item-board-{{$marketBoard->item->id}}');
    </script>
@endpush
