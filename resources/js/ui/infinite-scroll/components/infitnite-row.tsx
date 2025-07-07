import React from 'react';

import InfiniteRowBox from 'ui/infinite-scroll/components/infinite-row-box';
import InfiniteScroll from 'ui/infinite-scroll/infinite-scroll';
import InfiniteScrollProps from 'ui/infinite-scroll/types/infinite-scroll-props';

const InfiniteRow = ({
  children,
  handle_scroll,
  additional_css,
}: InfiniteScrollProps) => {
  return (
    <InfiniteScroll
      handle_scroll={handle_scroll}
      additional_css={additional_css}
    >
      <ul
        role="list"
        className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        {React.Children.map(children, (child, idx) => (
          <InfiniteRowBox key={idx}>{child}</InfiniteRowBox>
        ))}
      </ul>
    </InfiniteScroll>
  );
};

export default InfiniteRow;
