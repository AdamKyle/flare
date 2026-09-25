import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { AnimatePresence } from 'framer-motion';
import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useState } from 'react';

import MarketComparison from './market-comparison';
import MarketListingCard from './market-listing-card';
import { useCustomContext } from '../../../../utils/hooks/use-custom-context';
import { useInfiniteScroll } from '../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import MarketListingDefinition from '../api/definitions/market-listing-definition';
import { useBuyMarketListing } from '../api/hooks/use-buy-market-listing';
import { useMarketListings } from '../api/hooks/use-market-listings';
import { MarketContext } from '../context/market-context';
import { useMarketRealtimeRefresh } from '../hooks/use-market-realtime-refresh';
import { useOpenMarketItemDetails } from '../hooks/use-open-market-item-details';
import {
  MARKET_PRICE_SORT_OPTIONS,
  MARKET_TYPE_OPTIONS,
  resolveMarketPriceSort,
} from '../utils/build-market-filter-options';
import { isEquippableMarketType } from '../utils/is-equippable-market-type';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteRow from 'ui/infinite-scroll/components/infitnite-row';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MarketListings = (): ReactNode => {
  const {
    character_id: characterId,
    realtime_version: realtimeVersion,
    update_character: updateCharacter,
  } = useCustomContext(MarketContext, 'MarketListings');

  const {
    data,
    loading,
    error,
    isLoadingMore,
    onEndReached,
    setSearchText,
    setFilters,
    setPage,
    setRefresh,
  } = useMarketListings();

  const {
    buy,
    loading: isBuying,
    error: buyError,
  } = useBuyMarketListing(characterId);
  const { open_item_details: openItemDetails } = useOpenMarketItemDetails();
  const { handleScroll } = useInfiniteScroll({ on_end_reached: onEndReached });

  const [searchInput, setSearchInput] = useState('');
  const [comparedListing, setComparedListing] =
    useState<MarketListingDefinition | null>(null);
  const [buyingListingId, setBuyingListingId] = useState<number | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  useMarketRealtimeRefresh({
    realtime_version: realtimeVersion,
    set_page: setPage,
    set_refresh: setRefresh,
  });

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    [setSearchText]
  );

  useEffect(() => debouncedSetSearchText.cancel, [debouncedSetSearchText]);

  const handleSearch = (value: string) => {
    setSearchInput(value);
    debouncedSetSearchText(value.trim());
  };

  const handleTypeChange = (option: DropdownItem) => {
    setFilters((previousFilters) => ({
      ...previousFilters,
      type: `${option.value}`,
    }));
  };

  const handleClearType = () => {
    setFilters((previousFilters) => ({ ...previousFilters, type: null }));
  };

  const handleSortChange = (option: DropdownItem) => {
    setFilters((previousFilters) => ({
      ...previousFilters,
      sort_price: resolveMarketPriceSort(option),
    }));
  };

  const handleClearSort = () => {
    setFilters((previousFilters) => ({ ...previousFilters, sort_price: null }));
  };

  const handleBuy = async (listing: MarketListingDefinition) => {
    setSuccessMessage(null);
    setBuyingListingId(listing.id);

    const result = await buy(listing.id);

    setBuyingListingId(null);

    if (!result) {
      return;
    }

    updateCharacter({
      gold: result.gold,
      inventory_count: result.inventory_count,
    });

    setSuccessMessage(result.message);
  };

  const handlePurchasedFromComparison = (message: string) => {
    setComparedListing(null);
    setSuccessMessage(message);
  };

  const renderCompareAction = (listing: MarketListingDefinition): ReactNode => {
    if (
      listing.character_id === characterId ||
      !isEquippableMarketType(listing.type)
    ) {
      return null;
    }

    return (
      <Button
        label="Compare"
        variant={ButtonVariant.SUCCESS}
        on_click={() => setComparedListing(listing)}
        aria_label={`Compare ${listing.name}`}
      />
    );
  };

  const renderBuyAction = (listing: MarketListingDefinition): ReactNode => {
    if (listing.character_id === characterId) {
      return null;
    }

    return (
      <LoadingButton
        label="Buy"
        loading_label="Buying..."
        variant={ButtonVariant.PRIMARY}
        on_click={() => void handleBuy(listing)}
        is_loading={buyingListingId === listing.id}
        disabled={isBuying}
        aria_label={`Buy ${listing.name}`}
      />
    );
  };

  const renderListingActions = (
    listing: MarketListingDefinition
  ): ReactNode => (
    <>
      <Button
        label="View"
        variant={ButtonVariant.PRIMARY}
        on_click={() => openItemDetails(listing.item_id)}
        aria_label={`View ${listing.name}`}
      />
      {renderCompareAction(listing)}
      {renderBuyAction(listing)}
    </>
  );

  const renderListing = (listing: MarketListingDefinition): ReactNode => (
    <MarketListingCard
      key={listing.id}
      listing={listing}
      actions={renderListingActions(listing)}
      status={
        listing.character_id === characterId
          ? 'This is your listing.'
          : undefined
      }
    />
  );

  const renderLoadingMore = (): ReactNode => {
    if (!isLoadingMore) {
      return null;
    }

    return <InfiniteLoader />;
  };

  const renderListings = (): ReactNode => {
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (data.length === 0) {
      return (
        <p className="py-6 text-center text-sm text-gray-600 dark:text-gray-400">
          No listings match your filters.
        </p>
      );
    }

    return (
      <InfiniteRow handle_scroll={handleScroll} additional_css="max-h-150">
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
          {data.map(renderListing)}
        </div>
        {renderLoadingMore()}
      </InfiniteRow>
    );
  };

  const renderMessages = (): ReactNode => {
    if (buyError) {
      return <ApiErrorAlert apiError={buyError.message} />;
    }

    if (!successMessage) {
      return null;
    }

    return <Alert variant={AlertVariant.SUCCESS}>{successMessage}</Alert>;
  };

  const renderComparison = (): ReactNode => {
    if (!comparedListing) {
      return null;
    }

    return (
      <MarketComparison
        key={comparedListing.id}
        listing_id={comparedListing.id}
        listing_name={comparedListing.name}
        on_close={() => setComparedListing(null)}
        on_purchased={handlePurchasedFromComparison}
      />
    );
  };

  return (
    <div className="relative min-h-128 space-y-4">
      <div inert={comparedListing !== null} className="space-y-4">
        {renderMessages()}
        <div className="flex flex-col gap-3 md:flex-row md:items-center">
          <div className="flex-1">
            <Input
              value={searchInput}
              on_change={handleSearch}
              place_holder="Search listings by item name"
              aria_label="Search listings by item name"
              clearable
            />
          </div>
          <div className="w-full md:w-48">
            <Dropdown
              items={MARKET_TYPE_OPTIONS}
              on_select={handleTypeChange}
              on_clear={handleClearType}
              selection_placeholder="Filter by type"
            />
          </div>
          <div className="w-full md:w-48">
            <Dropdown
              items={MARKET_PRICE_SORT_OPTIONS}
              on_select={handleSortChange}
              on_clear={handleClearSort}
              selection_placeholder="Sort by price"
            />
          </div>
        </div>
        {renderListings()}
      </div>
      <AnimatePresence>{renderComparison()}</AnimatePresence>
    </div>
  );
};

export default MarketListings;
