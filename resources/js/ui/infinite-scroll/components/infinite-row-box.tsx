import React from 'react';

import InfiniteRowBoxProps from 'ui/infinite-scroll/types/components/infinite-row-box-props';

const InfiniteRowBox = ({ children }: InfiniteRowBoxProps) => {
  return (
    <li
      className="flex flex-col border border-gray-300 dark:border-gray-300 rounded-lg p-4 bg-white dark:bg-gray-700 shadow-sm cursor-auto"
      aria-labelledby="item-1-name"
    >
      {children}
    </li>
  );
};

export default InfiniteRowBox;
