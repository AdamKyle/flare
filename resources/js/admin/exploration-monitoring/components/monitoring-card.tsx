import React from 'react';

import { MonitoringCardProps } from '../types/component-props';

export default function MonitoringCard({
  title,
  description,
  children,
  onClick,
  ariaLabel,
}: MonitoringCardProps) {
  const classes =
    'rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5 ' +
    'dark:border-gray-700 dark:bg-gray-900';
  const content = (
    <>
      {title && (
        <div className="mb-4">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {title}
          </h2>
          {description && (
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
              {description}
            </p>
          )}
        </div>
      )}
      {children}
    </>
  );

  if (onClick) {
    return (
      <button
        type="button"
        className={`${classes} cursor-pointer text-left transition-colors hover:border-blue-400 focus:ring-2 focus:ring-blue-400 focus:outline-none dark:hover:border-blue-500`}
        onClick={onClick}
        aria-label={ariaLabel}
      >
        {content}
      </button>
    );
  }

  return <section className={classes}>{content}</section>;
}
