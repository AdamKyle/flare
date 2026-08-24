import React, { ReactNode, useEffect, useMemo, useState } from 'react';

import BatchCraftingElapsedTimeProps from './types/batch-crafting-elapsed-time-props';
import { formatBatchCraftingTimer } from '../utils/format-batch-crafting-duration';

const BatchCraftingElapsedTime = ({
  processing_started_at,
  completed_at,
}: BatchCraftingElapsedTimeProps): ReactNode => {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (completed_at) {
      return;
    }

    const interval = setInterval(() => {
      setNow(Date.now());
    }, 1000);

    return () => {
      clearInterval(interval);
    };
  }, [completed_at]);

  const elapsedSeconds = useMemo(() => {
    if (!processing_started_at) {
      return 0;
    }

    const startedAtMs = new Date(processing_started_at).getTime();
    const endMs = completed_at ? new Date(completed_at).getTime() : now;

    return Math.max(0, (endMs - startedAtMs) / 1000);
  }, [processing_started_at, completed_at, now]);

  return (
    <p className="text-sm">
      <span className="text-gray-600 dark:text-gray-400">Elapsed:</span>{' '}
      <span className="font-semibold">
        {formatBatchCraftingTimer(elapsedSeconds)}
      </span>
    </p>
  );
};

export default BatchCraftingElapsedTime;
