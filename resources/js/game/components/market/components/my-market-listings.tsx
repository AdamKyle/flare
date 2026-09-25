import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { AnimatePresence } from 'framer-motion';
import React, { ReactNode, useState } from 'react';

import EditMarketListing from './edit-market-listing';
import MarketListingCard from './market-listing-card';
import { useCustomContext } from '../../../../utils/hooks/use-custom-context';
import { useInfiniteScroll } from '../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import MarketListingDefinition from '../api/definitions/market-listing-definition';
import { useManageMarketListing } from '../api/hooks/use-manage-market-listing';
import { useOwnedMarketListings } from '../api/hooks/use-owned-market-listings';
import { MarketContext } from '../context/market-context';
import { useMarketRealtimeRefresh } from '../hooks/use-market-realtime-refresh';
import { useOpenMarketItemDetails } from '../hooks/use-open-market-item-details';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import InfiniteRow from 'ui/infinite-scroll/components/infitnite-row';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MyMarketListings = (): ReactNode => {
  const {
    character_id: characterId,
    realtime_version: realtimeVersion,
    update_character: updateCharacter,
  } = useCustomContext(MarketContext, 'MyMarketListings');

  const {
    data,
    loading,
    error,
    isLoadingMore,
    onEndReached,
    setPage,
    setRefresh,
  } = useOwnedMarketListings(characterId);

  const {
    begin_edit: beginEdit,
    update_price: updatePrice,
    cancel_edit: cancelEdit,
    delist,
    loading: isProcessing,
    error: manageError,
  } = useManageMarketListing(characterId);

  const { open_item_details: openItemDetails } = useOpenMarketItemDetails();
  const { handleScroll } = useInfiniteScroll({ on_end_reached: onEndReached });

  const [editingListing, setEditingListing] =
    useState<MarketListingDefinition | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  useMarketRealtimeRefresh({
    realtime_version: realtimeVersion,
    set_page: setPage,
    set_refresh: setRefresh,
  });

  const refreshOwnedListings = () => {
    setPage(1);
    setRefresh((previousRefresh) => !previousRefresh);
  };

  const handleBeginEdit = async (listing: MarketListingDefinition) => {
    setSuccessMessage(null);

    const result = await beginEdit(listing.id);

    if (!result) {
      return;
    }

    setEditingListing(result.listing);
  };

  const handleCloseEdit = () => {
    if (!editingListing) {
      return;
    }

    const listingId = editingListing.id;

    setEditingListing(null);

    void cancelEdit(listingId);
  };

  const handleSave = async (listedPrice: number) => {
    if (!editingListing) {
      return;
    }

    const result = await updatePrice(editingListing.id, listedPrice);

    if (!result) {
      return;
    }

    setEditingListing(null);
    setSuccessMessage(result.message ?? null);
    refreshOwnedListings();
  };

  const handleDelist = async (listingId: number) => {
    setSuccessMessage(null);

    const result = await delist(listingId);

    if (!result) {
      return;
    }

    updateCharacter({ inventory_count: result.inventory_count });

    setEditingListing(null);
    setSuccessMessage(result.message);
    refreshOwnedListings();
  };

  const renderListingActions = (
    listing: MarketListingDefinition
  ): ReactNode => (
    <>
      <Button
        label="View Item"
        variant={ButtonVariant.PRIMARY}
        on_click={() => openItemDetails(listing.item_id)}
        aria_label={`View ${listing.name}`}
      />
      <Button
        label="Edit Listing"
        variant={ButtonVariant.SUCCESS}
        on_click={() => void handleBeginEdit(listing)}
        disabled={isProcessing}
        aria_label={`Edit listing for ${listing.name}`}
      />
      <Button
        label="Delist"
        variant={ButtonVariant.DANGER}
        on_click={() => void handleDelist(listing.id)}
        disabled={isProcessing}
        aria_label={`Delist ${listing.name}`}
      />
    </>
  );

  const renderListing = (listing: MarketListingDefinition): ReactNode => (
    <MarketListingCard
      key={listing.id}
      listing={listing}
      actions={renderListingActions(listing)}
      status={listing.is_locked ? 'Locked while being edited.' : undefined}
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
          You have no items listed on the Market.
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
    if (manageError && !editingListing) {
      return <ApiErrorAlert apiError={manageError.message} />;
    }

    if (!successMessage) {
      return null;
    }

    return <Alert variant={AlertVariant.SUCCESS}>{successMessage}</Alert>;
  };

  const renderEditListing = (): ReactNode => {
    if (!editingListing) {
      return null;
    }

    return (
      <EditMarketListing
        key={editingListing.id}
        listing={editingListing}
        is_processing={isProcessing}
        error={manageError}
        on_save={(listedPrice) => void handleSave(listedPrice)}
        on_delist={() => void handleDelist(editingListing.id)}
        on_close={handleCloseEdit}
      />
    );
  };

  return (
    <div className="relative min-h-128 space-y-4">
      <div inert={editingListing !== null} className="space-y-4">
        {renderMessages()}
        {renderListings()}
      </div>
      <AnimatePresence>{renderEditListing()}</AnimatePresence>
    </div>
  );
};

export default MyMarketListings;
