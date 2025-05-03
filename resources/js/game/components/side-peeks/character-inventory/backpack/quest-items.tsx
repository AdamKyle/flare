import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce } from 'lodash';
import React, { ReactNode, useMemo } from 'react';

import InventoryList from './inventory-list';
import BackPackItemsProps from './types/back-pack-items-props';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';
import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

import { GameDataError } from 'game-data/components/game-data-error';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const QuestItems = ({
  character_id,
  on_switch_view,
}: BackPackItemsProps): ReactNode => {
  const { data, error, loading, setSearchText, onEndReached } =
    UsePaginatedApiHandler<BaseInventoryItemDefinition>({
      url: CharacterInventoryApiUrls.CHARACTER_QUEST_ITEMS,
      urlParams: { character: character_id },
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

  if (error) {
    return <GameDataError />;
  }

  if (loading) {
    return <InfiniteLoader />;
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
        <Input on_change={onSearch} clearable />
      </div>
      <div className="flex-1 min-h-0">
        <InventoryList
          items={data}
          is_quest_items={true}
          on_scroll_to_end={handleQuestItemsScroll}
        />
      </div>
    </div>
  );
};

export default QuestItems;
