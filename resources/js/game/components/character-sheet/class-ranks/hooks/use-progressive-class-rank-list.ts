import React, { useEffect, useState } from 'react';

import { CLASS_RANK_INFINITE_SCROLL_BATCH_SIZE } from '../constants/class-rank-list-constants';
import UseProgressiveClassRankListDefinition from './definitions/use-progressive-class-rank-list-definition';
import UseProgressiveClassRankListParams from './definitions/use-progressive-class-rank-list-params';

export const useProgressiveClassRankList = ({
  total_items: totalItems,
  reset_key: resetKey,
  initial_count: initialCount = CLASS_RANK_INFINITE_SCROLL_BATCH_SIZE,
}: UseProgressiveClassRankListParams): UseProgressiveClassRankListDefinition => {
  const [visibleCount, setVisibleCount] = useState<number>(
    Math.min(initialCount, totalItems)
  );

  useEffect(() => {
    setVisibleCount(Math.min(initialCount, totalItems));
  }, [totalItems, resetKey, initialCount]);

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
      Math.min(currentCount + CLASS_RANK_INFINITE_SCROLL_BATCH_SIZE, totalItems)
    );
  };

  return {
    visible_count: visibleCount,
    handle_scroll: handleScroll,
    has_more: hasMore,
  };
};
