import { isEmpty } from 'lodash';
import React, { ReactNode } from 'react';

import GemSlot from './gem-slot';
import { useInfiniteScroll } from './hooks/use-infinite-scroll';
import GemBagInfiniteScrollProps from './types/gem-bag-infinite-scroll-props';
import BaseGemDetails from '../../../../api-definitions/items/base-gem-details';

import { InfiniteScroll } from 'ui/infinite-scroll/infinite-scroll';

const GemBagInfiniteScroll = ({
  gems,
}: GemBagInfiniteScrollProps): ReactNode => {
  const { visibleItems: visibleGems, handleScroll: handleInventoryScroll } =
    useInfiniteScroll({ items: gems, chunkSize: 5 });

  return (
    <InfiniteScroll
      handle_scroll={handleInventoryScroll}
      additional_css={'my-4'}
    >
      {isEmpty(visibleGems) && (
        <div className="text-center py-4">
          You have no gems in your inventory. You can craft gems that you can
          socket in to gear
        </div>
      )}
      {visibleGems.map((gem) => (
        <GemSlot key={gem.slot_id} gem_slot={gem as BaseGemDetails} />
      ))}
    </InfiniteScroll>
  );
};

export default GemBagInfiniteScroll;
