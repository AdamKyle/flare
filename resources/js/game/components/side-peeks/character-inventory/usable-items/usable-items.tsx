import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce } from 'lodash';
import React, { useMemo } from 'react';

import UsableItemsProps from './types/usable-items-props';
import UsableItemsList from './usable-items-list';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

import { GameDataError } from 'game-data/components/game-data-error';

import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const UsableItems = ({ character_id }: UsableItemsProps) => {
  const { data, error, loading, setSearchText, setFilters, onEndReached } =
    UsePaginatedApiHandler<BaseInventoryItemDefinition>({
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
    return <GameDataError />;
  }

  if (loading) {
    return <InfiniteLoader />;
  }

  return (
    <div className="flex flex-col h-full overflow-hidden">
      <hr className="w-full border-t border-gray-300 dark:border-gray-600" />
      <div className="pt-2 px-4">
        <Input on_change={onSearch} clearable />
      </div>
      <div className="pb-4 px-4 mt-4">
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
      <div className="flex-1 min-h-0">
        <UsableItemsList
          items={data}
          on_scroll_to_end={handleInventoryScroll}
        />
      </div>
    </div>
  );
};

export default UsableItems;
