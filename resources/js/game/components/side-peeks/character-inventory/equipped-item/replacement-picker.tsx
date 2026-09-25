import ApiErrorAlert from 'api-handler/components/api-error-alert';
import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce } from 'lodash';
import React, { ReactNode, useEffect, useMemo, useRef, useState } from 'react';

import ReplacementComparison from './replacement-comparison';
import ReplacementPickerProps from './types/replacement-picker-props';
import { isItemCompatibleWithPosition } from './utils/is-item-compatible-with-position';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import GenericItemList from '../../components/items/generic-item-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ReplacementPicker = ({
  character_id,
  target_position,
  target_item_name,
  is_equipment_restricted,
  on_close,
  on_equipped,
}: ReplacementPickerProps): ReactNode => {
  const [selectedCandidate, setSelectedCandidate] =
    useState<EquippableItemWithBase | null>(null);

  const autoLoadedAtCountRef = useRef<number | null>(null);

  const {
    data,
    error,
    loading,
    canLoadMore,
    isLoadingMore,
    onEndReached,
    setSearchText,
  } = UsePaginatedApiHandler<EquippableItemWithBase>({
    url: CharacterInventoryApiUrls.CHARACTER_INVENTORY,
    urlParams: { character: character_id },
  });

  const { handleScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const compatibleItems = useMemo(
    () =>
      data.filter((item) =>
        isItemCompatibleWithPosition(item, target_position)
      ),
    [data, target_position]
  );

  const hasCompatibleItems = compatibleItems.length > 0;
  const loadedItemCount = data.length;

  const shouldAutoLoadNextPage =
    !hasCompatibleItems && canLoadMore && !loading && !isLoadingMore;

  const isSearchingForCompatibleItems =
    loading || (!hasCompatibleItems && (canLoadMore || isLoadingMore));

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    [setSearchText]
  );

  useEffect(() => {
    return () => {
      debouncedSetSearchText.cancel();
    };
  }, [debouncedSetSearchText]);

  useEffect(() => {
    if (!shouldAutoLoadNextPage) {
      return;
    }

    if (autoLoadedAtCountRef.current === loadedItemCount) {
      return;
    }

    autoLoadedAtCountRef.current = loadedItemCount;

    onEndReached();
  }, [shouldAutoLoadNextPage, loadedItemCount, onEndReached]);

  const handleSearch = (value: string): void => {
    autoLoadedAtCountRef.current = null;

    debouncedSetSearchText(value.trim());
  };

  const handleSelectCandidate = (slotId: number): void => {
    const candidate = compatibleItems.find((item) => item.slot_id === slotId);

    if (!candidate) {
      return;
    }

    setSelectedCandidate(candidate);
  };

  const handleCloseComparison = (): void => {
    setSelectedCandidate(null);
  };

  const renderItems = (): ReactNode => {
    if (error) {
      return (
        <div className="px-4">
          <ApiErrorAlert apiError={error.message} />
        </div>
      );
    }

    if (isSearchingForCompatibleItems && !hasCompatibleItems) {
      return (
        <div className="px-4" role="status" aria-live="polite">
          <span className="sr-only">
            Searching your inventory for compatible items.
          </span>
          <InfiniteLoader />
        </div>
      );
    }

    return (
      <GenericItemList
        items={compatibleItems}
        is_quest_items={false}
        is_selectable={false}
        on_scroll_to_end={handleScroll}
        on_click={handleSelectCandidate}
        empty_message="You have no items in your inventory that can replace this item."
      />
    );
  };

  const renderComparison = (): ReactNode => {
    if (selectedCandidate === null) {
      return null;
    }

    return (
      <ReplacementComparison
        character_id={character_id}
        candidate={selectedCandidate}
        target_position={target_position}
        is_equipment_restricted={is_equipment_restricted}
        on_close={handleCloseComparison}
        on_equipped={on_equipped}
      />
    );
  };

  return (
    <StackedCard
      on_close={on_close}
      aria_label="Choose Replacement"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <div className="flex flex-col gap-2 px-4 pt-4">
          <p className="text-sm text-gray-700 dark:text-gray-300">
            Select an item from your inventory to replace{' '}
            <strong>{target_item_name}</strong>.
          </p>
          <Input
            on_change={handleSearch}
            place_holder="Search items"
            clearable
          />
        </div>
        <div className="min-h-0 flex-1 px-4">{renderItems()}</div>
      </div>
      <AnimatePresence mode="wait">{renderComparison()}</AnimatePresence>
    </StackedCard>
  );
};

export default ReplacementPicker;
