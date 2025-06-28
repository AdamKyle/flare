import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce } from 'lodash';
import React, { ReactNode, useMemo } from 'react';

import SetChoices from './set-choices';
import SetsProps from './types/sets-props';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { ItemTypeToView } from '../../components/items/enums/item-type-to-view';
import GenericItemList from '../../components/items/generic-item-list';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

import { GameDataError } from 'game-data/components/game-data-error';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const Sets = ({ character_id }: SetsProps): ReactNode => {
  const { data, error, loading, onEndReached, setSearchText, setFilters } =
    UsePaginatedApiHandler<BaseInventoryItemDefinition>({
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
      <div className="pt-2 px-4">
        <SetChoices
          character_id={character_id}
          on_set_change={handleSetChange}
          on_set_selection_clear={handleClearSetSelection}
        />
      </div>
      <div className="flex-1 min-h-0">
        <GenericItemList
          items={data}
          is_quest_items={false}
          on_scroll_to_end={handleSetScrolling}
          items_view_type={ItemTypeToView.EQUIPPABLE}
        />
      </div>
    </div>
  );
};

export default Sets;
