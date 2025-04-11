import React from 'react';

export default interface UseInfiniteScrollDefinition {
  handleScroll: (e: React.UIEvent<HTMLDivElement>) => void;
}
