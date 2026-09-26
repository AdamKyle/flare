import clsx from 'clsx';
import React from 'react';

import InfiniteScrollProps from 'ui/infinite-scroll/types/infinite-scroll-props';

const InfiniteScroll = ({
  handle_scroll: handleScroll,
  children,
  additional_css: additionalCss,
  height_class: heightClass,
}: InfiniteScrollProps) => {
  return (
    <div
      className={clsx(
        heightClass ?? 'h-full',
        'overflow-y-auto px-2',
        'scrollbar-thumb-primary-300 scrollbar-track-primary-100 scrollbar-thin',
        'dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200',
        'scrollbar-thumb-rounded-md',
        additionalCss
      )}
      onScroll={handleScroll}
    >
      <div className="pb-2">{children}</div>
    </div>
  );
};

export default InfiniteScroll;
