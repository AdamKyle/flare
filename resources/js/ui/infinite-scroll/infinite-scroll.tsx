import clsx from 'clsx';
import React from 'react';

import InfiniteScrollProps from 'ui/infinite-scroll/types/infinite-scroll-props';

const InfiniteScroll = (props: InfiniteScrollProps) => {
  return (
    <div
      className={clsx(
        'h-full overflow-y-auto px-2',
        'scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100',
        'dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200',
        'scrollbar-thumb-rounded-md',
        props.additional_css
      )}
      onScroll={props.handle_scroll}
    >
      <div className="pb-8">{props.children}</div>
    </div>
  );
};

export default InfiniteScroll;
