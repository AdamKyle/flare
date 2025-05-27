import clsx from 'clsx';
import { intervalToDuration } from 'date-fns';
import React, { useEffect, useState } from 'react';
import { match, P } from 'ts-pattern';

import TimerBarProps from 'ui/timer-bar/types/timer-bar-props';
import { getColorLevel } from 'ui/timer-bar/util/get-color-level';

const TimerBar = ({ length, title, additional_css }: TimerBarProps) => {
  const [remaining, setRemaining] = useState(length);

  useEffect(() => {
    if (remaining <= 0) return;
    const timerId = setInterval(
      () => setRemaining((prev) => Math.max(prev - 1, 0)),
      1000
    );
    return () => clearInterval(timerId);
  }, [remaining]);

  const percent = Math.round((remaining / length) * 100);

  const [bgClass, darkBgClass] = getColorLevel(percent);

  const barClasses = clsx(
    'h-4 rounded transition-all duration-1000 ease-linear',
    bgClass,
    darkBgClass
  );

  const dur = intervalToDuration({ start: 0, end: remaining * 1000 });

  const formattedRemaining = match(dur)
    .with(
      P.when((d) => (d.days ?? 0) > 0),
      (d) => `${d.days} days`
    )
    .with(
      P.when((d) => (d.hours ?? 0) > 0),
      (d) => `${d.hours} hours`
    )
    .with(
      P.when((d) => (d.minutes ?? 0) > 0),
      (d) => `${d.minutes} minutes`
    )
    .otherwise((d) => `${d.seconds ?? 0} seconds`);

  return (
    <div className={clsx('w-full', additional_css)}>
      <div className="flex justify-between items-center mb-1">
        <span className="text-sm font-medium text-gray-900 dark:text-gray-300">
          {title}
        </span>
        <span className="text-sm font-mono text-gray-800 dark:text-gray-300">
          {formattedRemaining}
        </span>
      </div>
      <div
        role="progressbar"
        aria-valuemin={0}
        aria-valuemax={length}
        aria-valuenow={remaining}
        aria-label={title}
        className="relative h-4 bg-gray-200 rounded overflow-hidden dark:bg-gray-700"
      >
        <div className={barClasses} style={{ width: `${percent}%` }} />
      </div>
    </div>
  );
};

export default TimerBar;
