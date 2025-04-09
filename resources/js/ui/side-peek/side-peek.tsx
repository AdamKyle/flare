import clsx from 'clsx';
import React from 'react';

import {useSidePeekAccessibility} from "ui/side-peek/hooks/use-side-peek-accessibility";
import SidePeekProps from 'ui/side-peek/types/side-peek-props';

const SidePeek = (props: SidePeekProps) => {
  const { dialogRef, handleKeyDown, handleClickingOutside } =
    useSidePeekAccessibility({
      is_open: props.is_open,
      allow_clicking_outside: props.allow_clicking_outside,
      on_close: props.on_close,
    });

  return (
    <>
      {props.is_open && (
        <div
          className="fixed inset-0 z-40 bg-black bg-opacity-50 dark:bg-black/70"
          onClick={handleClickingOutside}
          aria-hidden="true"
        />
      )}
      <div
        ref={dialogRef}
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        aria-labelledby="sidepeek-title"
        onKeyDown={handleKeyDown}
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
              aria-label="Close panel"
            >
              <i className="fas fa-angle-double-right" aria-hidden="true"></i>
            </button>
            <h2
              id="sidepeek-title"
              className="text-lg font-semibold text-gray-900 dark:text-white"
            >
              {props.title}
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
