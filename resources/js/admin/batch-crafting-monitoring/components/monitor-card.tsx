import React from 'react';

import MonitorCardProps from '../types/monitor-card-props';

const monitorCardClasses =
  'rounded-lg border border-gray-200 bg-white p-4 shadow-sm ' +
  'dark:border-gray-700 dark:bg-gray-900 sm:p-5';

export default function MonitorCard({
  children,
  onClick,
  ariaLabel,
}: MonitorCardProps) {
  if (onClick) {
    return (
      <button
        type="button"
        className={`${monitorCardClasses} cursor-pointer text-left transition-colors hover:border-blue-400 focus:ring-2 focus:ring-blue-400 focus:outline-none dark:hover:border-blue-500`}
        onClick={onClick}
        aria-label={ariaLabel}
      >
        {children}
      </button>
    );
  }

  return <section className={monitorCardClasses}>{children}</section>;
}
