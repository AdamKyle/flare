import React from 'react';

import RewardQueueCard from './reward-queue-card';
import { Summary } from '../types/reward-queue';

const FILTERABLE_STATUSES = new Set([
  'pending',
  'processing',
  'resumable',
  'completed',
  'failed',
]);

export default function SummaryCards({
  summary,
  onFilter,
}: {
  summary: Summary;
  onFilter?: (status: string) => void;
}) {
  return (
    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
      {Object.entries(summary).map(([label, value]) => {
        const filterable = FILTERABLE_STATUSES.has(label) && onFilter != null;
        if (filterable) {
          return (
            <button
              key={label}
              type="button"
              className="cursor-pointer rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm transition-colors hover:border-blue-400 focus:ring-2 focus:ring-blue-400 focus:outline-none sm:p-5 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-500"
              onClick={() => onFilter(label)}
              aria-label={`Filter by ${label}`}
            >
              <div className="text-sm text-gray-600 capitalize dark:text-gray-300">
                {label}
              </div>
              <div className="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                {value}
              </div>
            </button>
          );
        }

        return (
          <RewardQueueCard key={label}>
            <div className="text-sm text-gray-600 capitalize dark:text-gray-300">
              {label}
            </div>
            <div className="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
              {value}
            </div>
          </RewardQueueCard>
        );
      })}
    </div>
  );
}
