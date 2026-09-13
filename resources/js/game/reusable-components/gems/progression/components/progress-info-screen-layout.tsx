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
  <div className="flex h-full min-h-0 flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
    <div className="flex items-center justify-between border-b-2 border-b-gray-400 pb-2 dark:border-b-gray-600">
      <h2 className="text-base font-semibold text-gray-900 dark:text-gray-100">
        {title}
      </h2>
      <button
        type="button"
        onClick={onClose}
        aria-label={`Close ${title}`}
        className="focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 flex h-9 w-9 items-center justify-center rounded-full text-gray-700 hover:bg-gray-300 focus:outline-none focus-visible:ring-2 dark:text-gray-300 dark:hover:bg-gray-600"
      >
        <i className="fas fa-times" aria-hidden="true" />
      </button>
    </div>
    {children}
  </div>
);

export default ProgressInfoScreenLayout;
