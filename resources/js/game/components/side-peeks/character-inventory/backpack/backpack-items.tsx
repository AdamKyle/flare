import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce, isNil } from 'lodash';
import React, { useMemo, useState } from 'react';

import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { ItemTypeToView } from '../../components/items/enums/item-type-to-view';
import GenericItemList from '../../components/items/generic-item-list';
import GenericItemProps from '../../components/items/types/generic-item-props';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import InventoryItem from '../inventory-item/inventory-item';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const BackpackItems = ({ character_id, on_switch_view }: GenericItemProps) => {
  const [itemId, setItemId] = useState<number | null>(null);

  const { data, error, loading, setSearchText, onEndReached } =
    UsePaginatedApiHandler<EquippableItemWithBase>({
      url: CharacterInventoryApiUrls.CHARACTER_INVENTORY,
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

  const handleOnItemClick = (typeOfItem: ItemTypeToView, item_id: number) => {
    setItemId(item_id);
  };

  const closeItemView = () => {
    setItemId(null);
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

  if (!isNil(itemId)) {
    return (
      <InventoryItem
        item_id={itemId}
        character_id={character_id}
        type_of_item={ItemTypeToView.EQUIPPABLE}
        close_item_view={closeItemView}
      />
    );
  }

  return (
    <div className="flex flex-col h-full overflow-hidden">
      <div className="flex justify-center p-4">
        <Button
          on_click={() => on_switch_view(false)}
          label="Quest Items"
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
          is_quest_items={false}
          on_scroll_to_end={handleInventoryScroll}
          items_view_type={ItemTypeToView.EQUIPPABLE}
          on_click={handleOnItemClick}
        />
      </div>
    </div>
  );
};

export default BackpackItems;
