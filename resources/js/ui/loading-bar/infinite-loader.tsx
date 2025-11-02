import React, { ReactNode } from 'react';

const InfiniteLoader = (): ReactNode => (
  <div className="my-4 flex w-full flex-col items-center space-y-2">
    <div className="flex w-full justify-between text-xs">
      <span className="text-gray-600 dark:text-gray-300">Loading...</span>
      <span className="text-gray-600 dark:text-gray-300"></span>
    </div>
    <div className="bg-danube-100 dark:bg-danube-800 h-1.5 w-full overflow-hidden">
      <div className="bg-danube-500 dark:bg-danube-300 h-full w-full origin-[0%_50%] animate-[progress_1s_infinite_linear]"></div>
    </div>
  </div>
);

export default InfiniteLoader;
