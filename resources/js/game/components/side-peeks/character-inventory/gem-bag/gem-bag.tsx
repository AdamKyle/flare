import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { debounce } from 'lodash';
import React, { useMemo, useState } from 'react';

import GemDetails from './gem-details';
import GemList from './gem-list';
import GemBagProps from './types/gem-bag-props';
import BaseGemDetails from '../../../../api-definitions/items/base-gem-details';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GemBag = ({ character_id }: GemBagProps) => {
  const [gemToView, setGemToView] = useState<BaseGemDetails | null>(null);

  const { data, error, loading, setSearchText, onEndReached } =
    UsePaginatedApiHandler<BaseGemDetails>({
      url: CharacterInventoryApiUrls.CHARACTER_GEM_BAG,
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

  const { handleScroll: handleGemBagScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  const handleViewGem = (slotId: number) => {
    const foundGem = data.find((gem) => gem.slot_id === slotId);

    if (!foundGem) {
      return;
    }

    setGemToView(foundGem);
  };

  const handleCloseGemView = () => {
    setGemToView(null);
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

  if (gemToView) {
    return <GemDetails gem={gemToView} on_close={handleCloseGemView} />;
  }

  return (
    <div className="flex flex-col h-full overflow-hidden">
      <div className="pt-2 px-4">
        <Input on_change={onSearch} place_holder={'Search gems'} clearable />
      </div>
      <div className="flex-1 min-h-0">
        <GemList
          gems={data}
          on_scroll_to_end={handleGemBagScroll}
          on_view_gem={handleViewGem}
        />
      </div>
    </div>
  );
};

export default GemBag;
