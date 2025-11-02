import React from 'react';

const InfiniteLoaderRoseDanube = () => {
  return (
    <div className="my-4 flex w-full flex-col items-center space-y-2">
      <div className="flex w-full justify-between text-xs">
        <span className="text-gray-600 dark:text-gray-300">Loading...</span>
        <span className="text-gray-600 dark:text-gray-300"></span>
      </div>
      <div className="bg-danube-100 dark:bg-danube-800 h-3.5 w-full overflow-hidden">
        <div className="loader-colors loader-anim h-full w-full origin-[0%_50%]"></div>
      </div>
    </div>
  );
};

export default InfiniteLoaderRoseDanube;
