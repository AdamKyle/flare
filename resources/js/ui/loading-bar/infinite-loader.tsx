import React, { ReactNode } from 'react';

const InfiniteLoader = (): ReactNode => (
  <div className="w-full flex flex-col items-center space-y-2 my-4">
    <div className="w-full text-xs flex justify-between">
      <span className="text-gray-600 dark:text-gray-300">Loading...</span>
      <span className="text-gray-600 dark:text-gray-300"></span>
    </div>
    <div className="w-full h-1.5 bg-danube-100 dark:bg-danube-800 overflow-hidden">
      <div className="animate-[progress_1s_infinite_linear] w-full h-full bg-danube-500 dark:bg-danube-300 origin-[0%_50%]"></div>
    </div>
  </div>
);

export default InfiniteLoader;
