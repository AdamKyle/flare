import clsx from 'clsx';
import React from 'react';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

const SidePeek = (props: SidePeekProps) => {
  const handleClickingOutSide = () => {
    if (!props.allow_clicking_outside) {
      return;
    }

    props.on_close();
  };

  return (
    <>
      {props.is_open && (
        <div
          className="fixed inset-0 z-40 bg-black bg-opacity-50 dark:bg-black/70"
          onClick={handleClickingOutSide}
        />
      )}
      <div
        className={clsx(
          'fixed top-0 right-0 h-full bg-white dark:bg-gray-800 shadow-lg z-50 transition-transform duration-300 ease-in-out',
          props.is_open ? 'translate-x-0' : 'translate-x-full',
          'w-full sm:w-1/4'
        )}
      >
        <div className="flex justify-between items-center p-4 border-b dark:border-gray-700">
          <div className="flex items-center gap-2">
            <button
              onClick={props.on_close}
              className="text-gray-700 dark:text-white px-2 py-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
            >
              <i className="fas fa-angle-double-right"></i>
            </button>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
              Side Peek
            </h2>
          </div>
        </div>
        <div className="p-4 text-gray-900 dark:text-gray-100">
          {props.children}
        </div>
      </div>
    </>
  );
};

export default SidePeek;
