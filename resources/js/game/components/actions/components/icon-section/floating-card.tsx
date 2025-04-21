import React, { ReactNode } from 'react';

import FloatingCardProps from './types/floating-card-props';

const FloatingCard = (props: FloatingCardProps): ReactNode => {
  return (
    <div className="shadow-lg rounded-sm border border-gray-500 dark:border-gray-700 w-[20rem] md:w-[28rem] z-20 max-w-none text-black dark:text-gray-300">
      <div className="bg-gray-400 dark:bg-gray-700 border-b-2 border-b-gray-500 dark:border-b-gray-600 px-4 py-3 flex items-center justify-between">
        <h3 className="text-lg font-semibold mb-0">{props.title}</h3>
        <button
          className="p-0 bg-transparent border-none cursor-pointer transition-all duration-300 ease-in-out transform hover:scale-105"
          onClick={props.close_action}
          aria-label="Close"
        >
          <i
            className="fas fa-times-circle text-rose-600 dark:text-rose-500 rounded-full text-lg p-1"
            aria-hidden="true"
          ></i>
        </button>
      </div>
      <div className="p-4 bg-gray-200 dark:bg-gray-500">{props.children}</div>
    </div>
  );
};

export default FloatingCard;
