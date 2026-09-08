import React, { useEffect, useState } from 'react';

import { ACTIVE_BOON_BATCH_SIZE } from '../constants/active-boon-list-constants';
import UseProgressiveActiveBoonsDefinition, {
  UseProgressiveActiveBoonsParams,
} from './definitions/use-progressive-active-boons-definition';

export const useProgressiveActiveBoons = ({
  total_items: totalItems,
  reset_key: resetKey,
}: UseProgressiveActiveBoonsParams): UseProgressiveActiveBoonsDefinition => {
  const [visibleCount, setVisibleCount] = useState<number>(
    Math.min(ACTIVE_BOON_BATCH_SIZE, totalItems)
  );

  useEffect(() => {
    setVisibleCount(Math.min(ACTIVE_BOON_BATCH_SIZE, totalItems));
  }, [totalItems, resetKey]);

  const hasMore = visibleCount < totalItems;

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const { scrollTop, scrollHeight, clientHeight } = event.currentTarget;

    if (scrollTop + clientHeight < scrollHeight - 10) {
      return;
    }

    if (!hasMore) {
      return;
    }

    setVisibleCount((currentCount) =>
      Math.min(currentCount + ACTIVE_BOON_BATCH_SIZE, totalItems)
    );
  };

  return {
    visible_count: visibleCount,
    handle_scroll: handleScroll,
    has_more: hasMore,
  };
};
