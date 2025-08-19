import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce, isNil } from 'lodash';
import React, { useMemo, useState } from 'react';

import LocationDroppableItemProps from './types/location-droppable-items-props';
import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import ItemDetailsContainer from '../../components/items/details/item-details-container';
import { ItemTypeToView } from '../../components/items/enums/item-type-to-view';
import GenericItemList from '../../components/items/generic-item-list';
import { LocationApiUrls } from '../api/enums/location-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LocationDroppableItems = ({
  location_id,
  go_back,
}: LocationDroppableItemProps) => {
  const [isQuestItemDetailsOpen, setIsQuestItemDetailsOpen] = useState(false);
  const [itemTypeToView, setItemTypeToView] = useState<ItemTypeToView | null>(
    null
  );
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

  const onOpenQuestItemDetails = (
    itemTypeToView: ItemTypeToView,
    itemId: number
  ) => {
    const item = data.find((item) => item.id === itemId);

    if (!item) {
      return;
    }

    setIsQuestItemDetailsOpen(true);
    setItemTypeToView(itemTypeToView);
    setItemToView(item);
  };

  const close_quest_item_details = () => {
    setIsQuestItemDetailsOpen(false);
    setItemToView(null);
    setItemTypeToView(null);
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

  if (isQuestItemDetailsOpen && !isNil(itemToView) && !isNil(itemTypeToView)) {
    return (
      <ItemDetailsContainer
        item={itemToView}
        is_found_at_location
        item_type_to_view={itemTypeToView}
        on_close={close_quest_item_details}
      />
    );
  }

  return (
    <div className="flex flex-col h-full overflow-hidden">
      <div className="flex justify-center p-4">
        <Button
          on_click={() => go_back()}
          label="Back to location"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      <div className="pt-2 px-4">
        <Input on_change={onSearch} place_holder={'Search items'} clearable />
      </div>
      <div className="flex-1 min-h-0">
        <GenericItemList
          items={data}
          is_quest_items={true}
          on_scroll_to_end={handleQuestItemsScroll}
          items_view_type={ItemTypeToView.QUEST}
          on_click={onOpenQuestItemDetails}
        />
      </div>
    </div>
  );
};

export default LocationDroppableItems;
