import React, { ReactNode, useEffect, useMemo, useState } from 'react';

import BatchCraftingNextAttemptCountdownProps from './types/batch-crafting-next-attempt-countdown-props';

import TimerBarSize from 'ui/timer-bar/enums/timer-bar-size';
import TimerBar from 'ui/timer-bar/timer-bar';

const WINDOW_DURATION_SECONDS = 60;

const BatchCraftingNextAttemptCountdown = ({
  next_attempt_at,
}: BatchCraftingNextAttemptCountdownProps): ReactNode => {
  const nextAttemptAtMs = useMemo(
    () => new Date(next_attempt_at).getTime(),
    [next_attempt_at]
  );

  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (Date.now() >= nextAttemptAtMs) {
      return;
    }

    const interval = setInterval(() => {
      const currentTime = Date.now();

      setNow(currentTime);

      if (currentTime >= nextAttemptAtMs) {
        clearInterval(interval);
      }
    }, 1000);

    return () => {
      clearInterval(interval);
    };
  }, [nextAttemptAtMs]);

  const remainingSeconds = Math.max(
    0,
    Math.round((nextAttemptAtMs - now) / 1000)
  );

  if (remainingSeconds <= 0) {
    return (
      <p role="status" aria-live="polite" className="text-sm font-medium">
        Resuming...
      </p>
    );
  }

  return (
    <TimerBar
      length={WINDOW_DURATION_SECONDS}
      remaining={remainingSeconds}
      title="Next batch in"
      size={TimerBarSize.THIN}
    />
  );
};

export default BatchCraftingNextAttemptCountdown;
