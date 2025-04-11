import React from 'react';

import UseInfiniteScrollDefinition from './definition/use-infinite-scroll-definition';
import UseInfiniteScrollParams from './definition/use-infinite-scroll-params';

export const useInfiniteScroll = ({
                                    on_end_reached
                                  }: UseInfiniteScrollParams): UseInfiniteScrollDefinition => {
  const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
    const { scrollTop, scrollHeight, clientHeight } = e.currentTarget;
    if (scrollTop + clientHeight >= scrollHeight - 10) {
      on_end_reached();
    }
  };

  return { handleScroll };
};
