import React, { ReactNode } from 'react';

import { buildAreaGemEffectGroups } from './area-gem-context';
import GemEffectRow from './gem-effect-row';
import AreaGemContextProps from '../types/area-gem-context-props';

const SUMMARY_ROW_COUNT = 3;

/**
 * Compact, factual preview of a resolved Area Gem context's effective
 * effects: the highest-magnitude non-zero rows only, plus a total count.
 * `View Gem Effects` shows the complete breakdown behind this summary.
 */
const AreaGemEffectSummary = ({ context }: AreaGemContextProps): ReactNode => {
  const allRows = buildAreaGemEffectGroups(context).flatMap(
    (group) => group.rows
  );

  if (allRows.length === 0) {
    return (
      <p className="text-sm text-gray-600 dark:text-gray-400">
        This Gem World currently has no active effects.
      </p>
    );
  }

  const topRows = [...allRows]
    .sort((a, b) => b.value - a.value)
    .slice(0, SUMMARY_ROW_COUNT);
  const remainingCount = allRows.length - topRows.length;

  return (
    <div className="flex flex-col gap-1">
      <div className="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
        {topRows.map((row) => (
          <GemEffectRow
            key={row.label}
            field={row.field}
            label={row.label}
            value={row.value}
          />
        ))}
      </div>
      {remainingCount > 0 && (
        <p className="text-xs text-gray-500 dark:text-gray-400">
          +{remainingCount} more effect{remainingCount === 1 ? '' : 's'} — see
          View Gem Effects.
        </p>
      )}
    </div>
  );
};

export default AreaGemEffectSummary;
