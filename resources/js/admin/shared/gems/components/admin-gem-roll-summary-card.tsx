import React, { ReactNode } from 'react';

import AdminGemRollSummaryCardProps from './types/admin-gem-roll-summary-card-props';

const activeStyles =
  'border-de-york-400 dark:border-de-york-500 bg-de-york-100 dark:bg-de-york-100 hover:bg-de-york-200 dark:hover:bg-de-york-200 text-de-york-900 dark:text-de-york-900';

const inactiveStyles =
  'border-glacier-400 dark:border-glacier-500 bg-glacier-100 dark:bg-glacier-100 hover:bg-glacier-200 dark:hover:bg-glacier-200 text-glacier-900 dark:text-glacier-900';

/**
 * Compact entity card for one Gem roll inside the Rolled Profiles list.
 * Concise by design; full stats render in the roll detail SidePeek.
 */
const AdminGemRollSummaryCard = ({
  roll,
  on_click: onClick,
}: AdminGemRollSummaryCardProps): ReactNode => (
  <button
    type="button"
    onClick={onClick}
    aria-label={`View Roll #${roll.roll_number} details${roll.is_active ? ', currently active' : ''}`}
    className={`${roll.is_active ? activeStyles : inactiveStyles} focus-visible:ring-danube-500 dark:focus-visible:ring-danube-300 flex w-full items-center justify-between gap-2 rounded-lg border-2 p-3 text-left transition-colors focus:outline-none focus-visible:ring-2`}
  >
    <div className="flex min-w-0 flex-col gap-0.5">
      <span className="truncate font-semibold">Roll #{roll.roll_number}</span>
      <span className="truncate text-sm">{roll.name}</span>
    </div>
    {roll.is_active && (
      <span className="text-xs font-medium whitespace-nowrap">Active</span>
    )}
  </button>
);

export default AdminGemRollSummaryCard;
