import clsx from 'clsx';
import React from 'react';

import { useModalAccessibility } from 'ui/modal/hooks/use-modal-accessibility';
import ModalProps from 'ui/modal/types/modal-props';

const Modal = ({
  is_open,
  title,
  on_close,
  allow_clicking_outside = true,
  children,
}: ModalProps) => {
  const { dialogRef, handleKeyDown, handleClickingOutside } =
    useModalAccessibility({
      is_open,
      allow_clicking_outside,
      on_close,
    });

  if (!is_open) return null;

  return (
    <>
      <div
        className="fixed inset-0 z-40 flex items-center justify-center bg-black/50 dark:bg-black/70 overflow-hidden"
        onClick={handleClickingOutside}
        role="presentation"
        aria-hidden={!is_open}
      >
        <div
          ref={dialogRef}
          tabIndex={-1}
          role="dialog"
          aria-modal="true"
          aria-labelledby="modal-title"
          onKeyDown={handleKeyDown}
          className={clsx(
            'bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-full sm:max-w-3xl md:max-w-4xl lg:max-w-5xl mx-4 my-8',
            'flex flex-col focus:outline-none'
          )}
        >
          <div className="flex justify-between items-center mb-4">
            <h2
              id="modal-title"
              className="text-lg font-semibold text-gray-900 dark:text-white"
            >
              {title}
            </h2>
            <button
              onClick={on_close}
              className="text-gray-700 dark:text-white p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full"
              aria-label="Close modal"
            >
              <i className="fas fa-times" aria-hidden="true"></i>
            </button>
          </div>

          <div className="min-h-0 max-h-[550px] overflow-y-auto">
            {children}
          </div>
        </div>
      </div>
    </>
  );
};

export default Modal;
