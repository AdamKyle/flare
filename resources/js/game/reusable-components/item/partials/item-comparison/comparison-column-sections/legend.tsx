import React from 'react';

const Legend = () => {
  return (
    <div className="mt-4 mb-3 flex items-center justify-between text-sm leading-snug text-gray-700 dark:text-gray-300">
      <span>If equipped (net change)</span>
      <span className="flex items-center gap-3">
        <span className="flex items-center gap-1">
          <i
            className="fas fa-chevron-up text-emerald-600"
            aria-hidden="true"
          />
          <span>gain</span>
        </span>
        <span className="flex items-center gap-1">
          <i className="fas fa-chevron-down text-rose-600" aria-hidden="true" />
          <span>loss</span>
        </span>
      </span>
    </div>
  );
};

export default Legend;
