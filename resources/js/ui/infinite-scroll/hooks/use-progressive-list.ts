import React, { useEffect, useState } from 'react';

import UseProgressiveListDefinition from './definitions/use-progressive-list-definition';
import UseProgressiveListParams from './definitions/use-progressive-list-params';

export const useProgressiveList = ({
  total_items: totalItems,
  batch_size: batchSize,
  initial_count: initialCount = batchSize,
  reset_key: resetKey,
}: UseProgressiveListParams): UseProgressiveListDefinition => {
  const [visibleCount, setVisibleCount] = useState<number>(
    Math.min(initialCount, totalItems)
  );

  useEffect(() => {
    setVisibleCount(Math.min(initialCount, totalItems));
  }, [totalItems, resetKey, initialCount]);

  const hasMore = visibleCount < totalItems;

  const handleScroll = (event: React.UIEvent<HTMLDivElement>): void => {
    const { scrollTop, scrollHeight, clientHeight } = event.currentTarget;
    const isAtEnd = scrollTop + clientHeight >= scrollHeight - 10;

    if (!isAtEnd || !hasMore) {
      return;
    }

    setVisibleCount(Math.min(visibleCount + batchSize, totalItems));
  };

  return {
    visible_count: visibleCount,
    handle_scroll: handleScroll,
    has_more: hasMore,
  };
};
