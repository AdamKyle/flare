import React, { useState } from 'react';

import UseInfiniteScrollDefinition from './definition/use-infinite-scroll-definition';
import UseInfiniteScrollParams from './definition/use-infinite-scroll-params';
import UseInfiniteScroll from './state/use-infinite-scroll';

export const useInfiniteScroll = ({
  items,
  chunkSize = 5,
}: UseInfiniteScrollParams): UseInfiniteScrollDefinition => {
  const [visibleCount, setVisibleCount] =
    useState<UseInfiniteScroll['visibleCount']>(chunkSize);

  const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
    const { scrollTop, scrollHeight, clientHeight } = e.currentTarget;
    if (
      scrollTop + clientHeight >= scrollHeight &&
      visibleCount < items.length
    ) {
      setVisibleCount((prev) => Math.min(prev + chunkSize, items.length));
    }
  };

  const visibleItems = items.slice(0, visibleCount);

  return { visibleItems, handleScroll };
};
