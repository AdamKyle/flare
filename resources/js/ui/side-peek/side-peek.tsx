import clsx from 'clsx';
import React, { useEffect } from 'react';

import { useSidePeekAccessibility } from 'ui/side-peek/hooks/use-side-peek-accessibility';
import SidePeekProps from 'ui/side-peek/types/side-peek-props';

const SidePeek = (props: SidePeekProps) => {
  const { dialogRef, handleKeyDown, handleClickingOutside } =
    useSidePeekAccessibility({
      is_open: props.is_open,
      allow_clicking_outside: props.allow_clicking_outside,
      on_close: props.on_close,
    });

  useEffect(() => {
    const className = 'body-no-scroll';

    if (document.body.classList.contains(className)) {
      return;
    }

    if (props.is_open) {
      document.body.classList.add(className);
    } else {
      if (!document.body.classList.contains(className)) {
        return;
      }

      document.body.classList.remove(className);
    }

    return () => {
      document.body.classList.remove(className);
    };
  }, [props.is_open]);

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
          'fixed top-0 right-0 h-full w-full sm:w-1/4 z-50',
          'bg-white dark:bg-gray-800 shadow-lg',
          'transition-transform duration-300 ease-in-out',
          props.is_open ? 'translate-x-0' : 'translate-x-full',
          'flex flex-col'
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

        <div className="flex-1 min-h-0">{props.children}</div>
      </div>
    </>
  );
};

export default SidePeek;
