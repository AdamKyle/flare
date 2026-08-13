import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import { AnimatePresence } from 'framer-motion';
import { debounce } from 'lodash';
import React, { useEffect, useMemo, useState } from 'react';

import GemDetails from './gem-details';
import GemList from './gem-list';
import GemBagProps from './types/gem-bag-props';
import BaseGemDetails from '../../../../api-definitions/items/base-gem-details';
import { useInfiniteScroll } from '../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { CharacterInventoryApiUrls } from '../api/enums/character-inventory-api-urls';

import { GameDataError } from 'game-data/components/game-data-error';

import StackedCard from 'ui/cards/stacked-card';
import Input from 'ui/input/input';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GemBag = ({ character_id, initial_gem }: GemBagProps) => {
  const [gemToView, setGemToView] = useState<BaseGemDetails | null>(
    initial_gem ?? null
  );

  const { data, error, loading, setSearchText, onEndReached } =
    UsePaginatedApiHandler<BaseGemDetails>({
      url: CharacterInventoryApiUrls.CHARACTER_GEM_BAG,
      urlParams: { character: character_id },
    });

  useEffect(() => {
    if (!initial_gem) {
      return;
    }

    setGemToView(initial_gem);
  }, [initial_gem]);

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

  const renderGemView = () => {
    if (!gemToView) {
      return null;
    }

    return (
      <StackedCard on_close={handleCloseGemView}>
        <GemDetails gem={gemToView} />
      </StackedCard>
    );
  };

  return (
    <div className="flex h-full flex-col overflow-hidden">
      <div className="px-4 pt-2">
        <Input on_change={onSearch} place_holder={'Search gems'} clearable />
      </div>
      <div className="min-h-0 flex-1">
        <GemList
          gems={data}
          on_scroll_to_end={handleGemBagScroll}
          on_view_gem={handleViewGem}
        />
      </div>
      <AnimatePresence mode="wait">{renderGemView()}</AnimatePresence>
    </div>
  );
};

export default GemBag;
