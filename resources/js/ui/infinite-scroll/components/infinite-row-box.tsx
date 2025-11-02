import React from 'react';

import InfiniteRowBoxProps from 'ui/infinite-scroll/types/components/infinite-row-box-props';

const InfiniteRowBox = ({ children }: InfiniteRowBoxProps) => {
  return (
    <li
      className="flex cursor-auto flex-col rounded-lg border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-300 dark:bg-gray-700"
      aria-labelledby="item-1-name"
    >
      {children}
    </li>
  );
};

export default InfiniteRowBox;
