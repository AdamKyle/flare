import React, { ReactNode, useEffect, useMemo, useState } from 'react';

import BatchCraftingCountdownProps from './types/batch-crafting-countdown-props';

import TimerBarSize from 'ui/timer-bar/enums/timer-bar-size';
import TimerBar from 'ui/timer-bar/timer-bar';

const BatchCraftingCountdown = ({
  started_at,
  scheduled_for,
}: BatchCraftingCountdownProps): ReactNode => {
  const scheduledForMs = useMemo(
    () => new Date(scheduled_for).getTime(),
    [scheduled_for]
  );
  const startedAtMs = useMemo(
    () => new Date(started_at).getTime(),
    [started_at]
  );
  const totalDurationSeconds = Math.max(
    1,
    Math.round((scheduledForMs - startedAtMs) / 1000)
  );

  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (Date.now() >= scheduledForMs) {
      return;
    }

    const interval = window.setInterval(() => {
      const currentTime = Date.now();

      setNow(currentTime);

      if (currentTime >= scheduledForMs) {
        window.clearInterval(interval);
      }
    }, 1000);

    return () => {
      window.clearInterval(interval);
    };
  }, [scheduledForMs]);

  const remainingSeconds = Math.max(
    0,
    Math.round((scheduledForMs - now) / 1000)
  );

  if (remainingSeconds <= 0) {
    return (
      <p role="status" aria-live="polite" className="text-sm font-medium">
        Starting...
      </p>
    );
  }

  return (
    <TimerBar
      length={totalDurationSeconds}
      remaining={remainingSeconds}
      title="Starting in"
      size={TimerBarSize.THIN}
    />
  );
};

export default BatchCraftingCountdown;
