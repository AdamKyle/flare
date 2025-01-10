import React, { ReactNode } from 'react';

export default interface InfiniteScrollProps {
  handle_scroll: (e: React.UIEvent<HTMLDivElement>) => void;
  children: ReactNode;
  additional_css?: string;
}
