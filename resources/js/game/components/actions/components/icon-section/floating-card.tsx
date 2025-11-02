import React, { ReactNode } from 'react';

import FloatingCardProps from './types/floating-card-props';

const FloatingCard = (props: FloatingCardProps): ReactNode => {
  return (
    <div className="z-20 w-full max-w-[40rem] rounded-md border border-gray-500 text-black shadow-lg lg:w-[clamp(26rem,38vw,40rem)] dark:border-gray-700 dark:text-gray-300">
      <div className="flex items-center justify-between border-b-2 border-b-gray-500 bg-gray-400 px-4 py-3 dark:border-b-gray-600 dark:bg-gray-800">
        <h3 className="mb-0 text-lg font-semibold">{props.title}</h3>
        <button
          className="transform cursor-pointer border-none bg-transparent p-0 transition-all duration-300 ease-in-out hover:scale-105"
          onClick={props.close_action}
          aria-label="Close"
        >
          <i
            className="fas fa-times-circle rounded-full p-1 text-lg text-rose-600 dark:text-rose-500"
            aria-hidden="true"
          ></i>
        </button>
      </div>
      <div className="bg-gray-200 p-4 dark:bg-gray-700">{props.children}</div>
    </div>
  );
};

export default FloatingCard;
