import React, { ReactNode, useEffect, useMemo, useState } from 'react';

import ExplorationElapsedTimeProps from '../types/exploration-elapsed-time-props';
import { formatExplorationDuration } from '../utils/format-exploration-duration';

/**
 * Derives elapsed duration from `started_at` and the local clock instead of
 * a frozen server-provided duration, so the timer keeps ticking between
 * websocket updates and self-corrects after a refresh or tab sleep/resume.
 */
const ExplorationElapsedTime = ({
  started_at: startedAt,
  ended_at: endedAt,
}: ExplorationElapsedTimeProps): ReactNode => {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (endedAt) {
      return;
    }

    const interval = setInterval(() => {
      setNow(Date.now());
    }, 1000);

    return () => {
      clearInterval(interval);
    };
  }, [endedAt]);

  const elapsedSeconds = useMemo(() => {
    const startedAtMs = new Date(startedAt).getTime();
    const endMs = endedAt ? new Date(endedAt).getTime() : now;

    return Math.max(0, (endMs - startedAtMs) / 1000);
  }, [startedAt, endedAt, now]);

  return <>{formatExplorationDuration(elapsedSeconds)}</>;
};

export default ExplorationElapsedTime;
