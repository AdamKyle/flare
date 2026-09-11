import React, { ReactNode } from 'react';

import GemProgressHistoryRowCardProps from './types/gem-progress-history-row-card-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const GemProgressHistoryRowCard = ({
  row,
}: GemProgressHistoryRowCardProps): ReactNode => (
  <article className="border-glacier-200 bg-glacier-50 text-glacier-900 dark:border-glacier-700 dark:bg-glacier-900 dark:text-glacier-100 w-full rounded-lg border-2 p-4">
    <div className="flex items-center justify-between gap-2">
      <span className="text-base font-semibold">
        {row.generated_game_map_name ?? row.profile_name ?? '—'}
      </span>
      {row.is_current_profile ? (
        <span className="bg-de-york-100 text-de-york-800 dark:bg-de-york-900 dark:text-de-york-100 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
          Currently Applied
        </span>
      ) : (
        <span className="bg-glacier-100 text-glacier-800 dark:bg-glacier-800 dark:text-glacier-200 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap">
          Not Current
        </span>
      )}
    </div>

    <Dl>
      <Dt>Type</Dt>
      <Dd>{row.is_map_profile ? 'Map Gem World' : 'Location Gem World'}</Dd>
      <Dt>Source</Dt>
      <Dd>{row.map_name ?? '—'}</Dd>
      <Dt>Personal Level</Dt>
      <Dd>{row.personal_level}</Dd>
      <Dt>Global Level</Dt>
      <Dd>{row.global_level}</Dd>
    </Dl>
  </article>
);

export default GemProgressHistoryRowCard;
