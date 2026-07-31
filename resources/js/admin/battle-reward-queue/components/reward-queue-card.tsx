import React from 'react';

import { RewardQueueCardProps } from '../types/component-props';

export default function RewardQueueCard({
  title,
  description,
  children,
  className = '',
}: RewardQueueCardProps) {
  return (
    <section
      className={`rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-900 ${className}`}
    >
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
    </section>
  );
}
