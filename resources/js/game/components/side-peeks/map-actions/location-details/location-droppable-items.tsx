import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce, isNil } from 'lodash';
import React, { useMemo, useState } from 'react';

import LocationDroppableItemProps from './types/location-droppable-items-props';
import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import QuestItem from '../../character-inventory/inventory-item/quest-item';
import GenericItemList from '../../components/items/generic-item-list';
import { LocationApiUrls } from '../api/enums/location-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import StackedCard from 'ui/cards/stacked-card';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LocationDroppableItems = ({
  location_id,
  go_back,
}: LocationDroppableItemProps) => {
  const [isQuestItemDetailsOpen, setIsQuestItemDetailsOpen] = useState(false);
  const [itemToView, setItemToView] = useState<BaseQuestItemDefinition | null>(
    null
  );

  const { data, error, loading, setSearchText, onEndReached } =
    UsePaginatedApiHandler<BaseQuestItemDefinition>({
      url: LocationApiUrls.LOCATION_DROPPABLE_ITEMS,
      urlParams: { location: location_id },
    });

  const { handleScroll: handleQuestItemsScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    []
  );

  const onSearch = (value: string) => {
    debouncedSetSearchText(value.trim());
  };

  const onOpenQuestItemDetails = (itemId: number) => {
    const item = data.find((item) => item.item_id === itemId);

    if (!item) {
      return;
    }

    setIsQuestItemDetailsOpen(true);
    setItemToView(item);
  };

  const close_quest_item_details = () => {
    setIsQuestItemDetailsOpen(false);
    setItemToView(null);
  };

  if (error) {
    return (
      <div className={'p-4'}>
        <GameDataError />
      </div>
    );
  }

  if (loading) {
    return (
      <div className={'p-4'}>
        <InfiniteLoader />
      </div>
    );
  }

  const renderQuestItemView = () => {
    if (!isQuestItemDetailsOpen || isNil(itemToView)) {
      return null;
    }

    return (
      <StackedCard on_close={close_quest_item_details}>
        <QuestItem quest_item={itemToView} />
      </StackedCard>
    );
  };

  return (
    <>
      <div className="flex h-full flex-col overflow-hidden">
        <div className="flex justify-center p-4">
          <Button
            on_click={() => go_back()}
            label="Back to location"
            variant={ButtonVariant.PRIMARY}
          />
        </div>
        <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
        <div className="px-4 pt-2">
          <Input on_change={onSearch} place_holder={'Search items'} clearable />
        </div>
        <div className="min-h-0 flex-1">
          <GenericItemList
            items={data}
            is_quest_items={true}
            on_scroll_to_end={handleQuestItemsScroll}
            on_click={onOpenQuestItemDetails}
            use_item_id
          />
        </div>
      </div>
      <AnimatePresence mode="wait">{renderQuestItemView()}</AnimatePresence>
    </>
  );
};

export default LocationDroppableItems;
