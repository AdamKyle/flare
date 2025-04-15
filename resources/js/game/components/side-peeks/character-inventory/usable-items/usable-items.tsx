import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce } from 'lodash';
import React, { useMemo } from 'react';

import UsableItemsProps from './types/usable-items-props';
import UsableItemsList from './usable-items-list';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

import { GameDataError } from 'game-data/components/game-data-error';

import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Dropdown from "ui/drop-down/drop-down";
import {DropdownItem} from "ui/drop-down/types/drop-down-item";

const UsableItems = ({ character_id }: UsableItemsProps) => {
  const { data, error, loading, setSearchText, setFilters, onEndReached } =
    UsePaginatedApiHandler<BaseInventoryItemDefinition>({
      url: CharacterInventoryApiUrls.CHARACTER_USABLE_ITEMS,
      urlParams: { character: character_id },
    });

  const debouncedSetSearchText = useMemo(
    () => debounce((value: string) => setSearchText(value), 300),
    []
  );

  const onSearch = (value: string) => {
    debouncedSetSearchText(value);
  };

  const { handleScroll: handleInventoryScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const handleFilterChange = (value: DropdownItem) => {
    const [key, val] = Object.entries(value)[0];
    setFilters({ [val]: key });
  };

  const handleClearFilters = () => {
    setFilters({});
  }

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
        <Dropdown items={[
          {'Increase Stats': 'increase-stats'},
          {'Effects Skills': 'effects-skills'},
          {'Effects Base Modifiers': 'effects-base-modifiers'},
          {'Damages Kingdoms': 'damages-kingdoms'},
          {'Holy Oils': 'holy-oils'}
        ]} on_select={handleFilterChange} on_clear={handleClearFilters} />
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
