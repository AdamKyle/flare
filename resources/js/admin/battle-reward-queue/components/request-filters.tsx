import React from 'react';

import { RequestFiltersType } from '../types/reward-queue';
import { rewardQueueFilterOptions } from '../values/reward-queue-filter-options';

export default function RequestFilters({
  filters,
  onChange,
}: {
  filters: RequestFiltersType;
  onChange: (filters: RequestFiltersType) => void;
}) {
  return (
    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      {Object.entries(filters).map(([name, value]) => {
        const options =
          rewardQueueFilterOptions[
            name as keyof typeof rewardQueueFilterOptions
          ];
        const label = name.replaceAll('_', ' ');

        return (
          <label className="text-sm font-medium capitalize" key={name}>
            {label}
            {options ? (
              <select
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                value={value}
                onChange={(event) =>
                  onChange({
                    ...filters,
                    [name]: event.target.value,
                  })
                }
              >
                <option value="">All</option>
                {options.map((option) => (
                  <option value={option.value} key={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            ) : (
              <input
                className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                type={name.startsWith('date_') ? 'date' : 'text'}
                value={value}
                onChange={(event) =>
                  onChange({
                    ...filters,
                    [name]: event.target.value,
                  })
                }
              />
            )}
          </label>
        );
      })}
    </div>
  );
}
