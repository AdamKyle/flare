import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce } from 'lodash';
import React, { ReactNode, useMemo, useState } from 'react';

import SetChoices from './set-choices';
import SetsProps from './types/sets-props';
import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import GenericItemList from '../../components/items/generic-item-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import InventoryItem from '../inventory-item/inventory-item';

import { GameDataError } from 'game-data/components/game-data-error';

import StackedCard from 'ui/cards/stacked-card';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const Sets = ({ character_id }: SetsProps): ReactNode => {
  const [slotId, setSlotId] = useState<number | null>(null);

  const { data, error, loading, onEndReached, setSearchText, setFilters } =
    UsePaginatedApiHandler<EquippableItemWithBase>({
      url: CharacterInventoryApiUrls.CHARACTER_SET_ITEMS,
      urlParams: { character: character_id },
    });

  const { handleScroll: handleSetScrolling } = useInfiniteScroll({
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

  const handleSetChange = (selectedValue: DropdownItem) => {
    setFilters({
      set_id: selectedValue.value,
    });
  };

  const handleClearSetSelection = () => {
    setFilters({});
  };

  const handleOnItemClick = (slot_id: number) => {
    setSlotId(slot_id);
  };

  const closeItemView = () => {
    setSlotId(null);
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

  const renderInventoryItemView = () => {
    if (!slotId) {
      return null;
    }

    return (
      <StackedCard on_close={closeItemView}>
        <InventoryItem
          slot_id={slotId}
          character_id={character_id}
          on_action={() => {}}
        />
      </StackedCard>
    );
  };

  return (
    <div className="flex h-full flex-col overflow-hidden">
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      <div className="px-4 pt-2">
        <Input on_change={onSearch} place_holder={'Search items'} clearable />
      </div>
      <div className="px-4 pt-2">
        <SetChoices
          character_id={character_id}
          on_set_change={handleSetChange}
          on_set_selection_clear={handleClearSetSelection}
        />
      </div>
      <div className="min-h-0 flex-1">
        <GenericItemList
          items={data}
          is_quest_items={false}
          on_scroll_to_end={handleSetScrolling}
          on_click={handleOnItemClick}
        />
      </div>

      <AnimatePresence initial={false} mode="wait">
        {renderInventoryItemView()}
      </AnimatePresence>
    </div>
  );
};

export default Sets;
