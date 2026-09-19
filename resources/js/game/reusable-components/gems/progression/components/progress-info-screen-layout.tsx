import React, { ReactNode } from 'react';

import ProgressInfoScreenLayoutProps from './types/progress-info-screen-layout-props';

/**
 * Shared header/content wrapper for the Global and Personal progress info
 * screens so they render using the owning Map floating card's surface
 * instead of a separate nested card.
 */
const ProgressInfoScreenLayout = ({
  title,
  on_close: onClose,
  children,
}: ProgressInfoScreenLayoutProps): ReactNode => (
  <div className="absolute inset-0 z-10 flex h-full min-h-0 flex-col overflow-hidden bg-gray-200 px-4 py-4 sm:px-5 dark:bg-gray-700">
    <div className="flex shrink-0 items-center justify-between border-b-2 border-b-gray-400 pb-2 dark:border-b-gray-600">
      <h2 className="text-base font-semibold text-gray-900 dark:text-gray-100">
        {title}
      </h2>
      <button
        type="button"
        onClick={onClose}
        autoFocus
        aria-label={`Close ${title}`}
        className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 flex h-9 w-9 items-center justify-center rounded-full text-gray-700 hover:bg-gray-300 focus:outline-none focus-visible:ring-2 dark:text-gray-300 dark:hover:bg-gray-600"
      >
        <i className="fas fa-times" aria-hidden="true" />
      </button>
    </div>
    <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pt-4">
      {children}
    </div>
  </div>
);

export default ProgressInfoScreenLayout;
