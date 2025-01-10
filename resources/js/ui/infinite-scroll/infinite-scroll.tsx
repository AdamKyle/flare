import clsx from 'clsx';
import React, { ReactNode } from 'react';

import InfiniteScrollProps from 'ui/infinite-scroll/types/infinite-scroll-props';

export const InfiniteScroll = (props: InfiniteScrollProps): ReactNode => {
  return (
    <div
      className={clsx(
        'h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-primary-300 scrollbar-track-primary-100 dark:scrollbar-thumb-primary-400 dark:scrollbar-track-primary-200 scrollbar-thumb-rounded-md px-2',
        props.additional_css
      )}
      onScroll={props.handle_scroll}
    >
      {props.children}
    </div>
  );
};
