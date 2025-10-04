import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce } from 'lodash';
import React, { ReactNode, useMemo, useState } from 'react';

import BaseQuestItemDefinition from '../../../../api-definitions/items/quest-item-definitions/base-quest-item-definition';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import GenericItemList from '../../components/items/generic-item-list';
import GenericItemProps from '../../components/items/types/generic-item-props';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import QuestItem from '../inventory-item/quest-item';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const QuestItems = ({
  character,
  on_switch_view,
}: GenericItemProps): ReactNode => {
  const [itemToView, setItemToView] = useState<BaseQuestItemDefinition | null>(
    null
  );

  const { data, error, loading, setSearchText, onEndReached } =
    UsePaginatedApiHandler<BaseQuestItemDefinition>({
      url: CharacterInventoryApiUrls.CHARACTER_QUEST_ITEMS,
      urlParams: { character: character.id },
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

  const handleOnItemClick = (item_id: number) => {
    if (!data) {
      return;
    }

    const foundItem = data.find((item) => item.item_id === item_id);

    if (!foundItem) {
      return;
    }

    setItemToView(foundItem);
  };

  const handleCloseQuestDetails = () => {
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

  if (itemToView) {
    return (
      <QuestItem quest_item={itemToView} on_close={handleCloseQuestDetails} />
    );
  }

  return (
    <div className="flex flex-col h-full overflow-hidden">
      <div className="flex justify-center p-4">
        <Button
          on_click={() => on_switch_view(true)}
          label="Inventory Items"
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
          on_click={handleOnItemClick}
          use_item_id
        />
      </div>
    </div>
  );
};

export default QuestItems;
