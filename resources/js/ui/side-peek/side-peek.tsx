import clsx from "clsx";
import React from 'react';

import SidePeekProps from 'ui/side-peek/types/side-peek-props';

const SidePeek = (props: SidePeekProps) => {

  const handleClickingOutSide = () => {
    if (!props.allow_clicking_outside) {
      return;
    }

    props.on_close();
  }

  return (
    <>
      <div
        className="fixed inset-0 z-40 bg-black bg-opacity-50"
        onClick={handleClickingOutSide}
      />
      <div
        className={clsx(
          'fixed top-0 right-0 h-full bg-white shadow-lg z-50 transition-transform duration-300 ease-in-out',
          props.is_open ? 'translate-x-0' : 'translate-x-full',
          'w-full sm:w-1/4'
        )}
      >
        <div className="flex justify-between items-center p-4 border-b">
          <h2 className="text-lg font-semibold">Side Peek</h2>
          <button onClick={props.on_close}>
            <i className="fa-solid fa-times text-xl text-gray-700"></i>
          </button>
        </div>
        <div className="p-4">
          <p>This is the side panel content.</p>
        </div>
      </div>
    </>
  );
};

export default SidePeek;
