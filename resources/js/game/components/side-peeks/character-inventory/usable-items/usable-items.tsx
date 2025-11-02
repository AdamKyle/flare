import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce } from 'lodash';
import React, { useMemo, useState } from 'react';

import UsableItemsProps from './types/usable-items-props';
import UsableItem from './usable-item';
import BaseUsableItemDefinition from '../../../../api-definitions/items/usable-item-definitions/base-usable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import UsableItemsList from '../../components/items/usable-items-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import StackedCard from 'ui/cards/stacked-card';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const UsableItems = ({ character_id }: UsableItemsProps) => {
  const [itemToView, setItemToView] = useState<BaseUsableItemDefinition | null>(
    null
  );

  const { data, error, loading, setSearchText, setFilters, onEndReached } =
    UsePaginatedApiHandler<BaseUsableItemDefinition>({
      url: CharacterInventoryApiUrls.CHARACTER_USABLE_ITEMS,
      urlParams: { character: character_id },
    });

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    []
  );

  const onSearch = (value: string) => {
    debouncedSetSearchText(value.trim());
  };

  const onViewItem = (itemId: number) => {
    const foundItem = data.find((item) => item.item_id === itemId);

    if (!foundItem) {
      return;
    }

    setItemToView(foundItem);
  };

  const onCloseViewItem = () => {
    setItemToView(null);
  };

  const { handleScroll: handleInventoryScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const handleFilterChange = (dropDownValue: DropdownItem) => {
    setFilters({
      [dropDownValue.value]: true,
    });
  };

  const handleClearFilters = () => {
    setFilters({});
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

  const renderUsableItemView = () => {
    if (!itemToView) {
      return null;
    }

    return (
      <StackedCard on_close={onCloseViewItem}>
        <UsableItem item={itemToView} />
      </StackedCard>
    );
  };

  return (
    <>
      <div className="flex h-full flex-col overflow-hidden">
        <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
        <div className="px-4 pt-2">
          <Input on_change={onSearch} place_holder={'Search items'} clearable />
        </div>
        <div className="mt-4 px-4 pb-4">
          <Dropdown
            items={[
              { label: 'Increase Stats', value: 'increase-stats' },
              { label: 'Effects Skills', value: 'effects-skills' },
              {
                label: 'Effects Base Modifiers',
                value: 'effects-base-modifiers',
              },
              { label: 'Damages Kingdoms', value: 'damages-kingdoms' },
              { label: 'Holy Oils', value: 'holy-oils' },
            ]}
            selection_placeholder={'Filter items by'}
            on_select={handleFilterChange}
            on_clear={handleClearFilters}
          />
        </div>
        <div className="min-h-0 flex-1">
          <UsableItemsList
            items={data}
            on_scroll_to_end={handleInventoryScroll}
            on_item_clicked={onViewItem}
          />
        </div>
      </div>
      <AnimatePresence mode="wait">{renderUsableItemView()}</AnimatePresence>
    </>
  );
};

export default UsableItems;
