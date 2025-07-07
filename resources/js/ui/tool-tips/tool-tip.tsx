import React from 'react';

import ToolTipProps from 'ui/tool-tips/types/tool-tip-props';

const ToolTip = ({ children }: ToolTipProps) => {
  return (
    <span className="relative inline-block group">
      <button
        type="button"
        aria-describedby={'tool-tip'}
        className="focus:outline-none"
      >
        <i className="fas fa-info-circle" aria-hidden="true" />
        <span className="sr-only">Info</span>
      </button>

      <div
        role="tooltip"
        className={
          'absolute z-10 w-48 p-2 mt-1 bg-white text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200 ' +
          'border border-gray-200 rounded shadow-lg break-words transition-opacity duration-200 ' +
          'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 ' +
          'pointer-events-none group-hover:pointer-events-auto group-focus-within:pointer-events-auto'
        }
      >
        {children}
      </div>
    </span>
  );
};

export default ToolTip;
