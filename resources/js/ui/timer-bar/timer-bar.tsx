import clsx from 'clsx';
import { intervalToDuration } from 'date-fns';
import React, { useEffect, useState } from 'react';
import { match, P } from 'ts-pattern';

import TimerBarSize from 'ui/timer-bar/enums/timer-bar-size';
import { timerBarSizeStyles } from 'ui/timer-bar/styles/timer-bar-size-styles';
import TimerBarProps from 'ui/timer-bar/types/timer-bar-props';
import { getColorLevel } from 'ui/timer-bar/util/get-color-level';

const pluralizeUnit = (value: number, unit: string): string =>
  `${value} ${unit}${value === 1 ? '' : 's'}`;

const formatRemainingTime = (remainingSeconds: number): string => {
  const duration = intervalToDuration({
    start: 0,
    end: remainingSeconds * 1000,
  });

  return match(duration)
    .with(
      P.when((d) => (d.days ?? 0) > 0),
      (d) => pluralizeUnit(d.days ?? 0, 'day')
    )
    .with(
      P.when((d) => (d.hours ?? 0) > 0),
      (d) => pluralizeUnit(d.hours ?? 0, 'hour')
    )
    .with(
      P.when((d) => (d.minutes ?? 0) > 0),
      (d) => pluralizeUnit(d.minutes ?? 0, 'minute')
    )
    .otherwise((d) => pluralizeUnit(d.seconds ?? 0, 'second'));
};

const TimerBar = ({
  length,
  title,
  remaining: controlledRemaining,
  size = TimerBarSize.DEFAULT,
  additional_css,
}: TimerBarProps) => {
  const [internalRemaining, setInternalRemaining] = useState(length);

  const isControlled = controlledRemaining !== undefined;

  useEffect(() => {
    if (isControlled) {
      return;
    }

    setInternalRemaining(length);

    if (length <= 0) {
      return;
    }

    const timerId = setInterval(() => {
      setInternalRemaining((prev) => {
        const next = Math.max(prev - 1, 0);

        if (next <= 0) {
          clearInterval(timerId);
        }

        return next;
      });
    }, 1000);

    return () => {
      clearInterval(timerId);
    };
  }, [isControlled, length]);

  const displayedRemaining = isControlled
    ? controlledRemaining
    : internalRemaining;
  const clampedRemaining = Math.min(Math.max(displayedRemaining, 0), length);
  const safeLength = length > 0 ? length : 1;
  const percent = Math.round((clampedRemaining / safeLength) * 100);

  const [bgClass, darkBgClass] = getColorLevel(percent);

  const barClasses = clsx(
    'h-full rounded transition-all duration-1000 ease-linear',
    bgClass,
    darkBgClass
  );

  const formattedRemaining = formatRemainingTime(clampedRemaining);

  return (
    <div className={clsx('w-full', additional_css)}>
      <div className="mb-1 flex items-center justify-between">
        <span className="text-sm font-medium text-gray-900 dark:text-gray-300">
          {title}
        </span>
        <span className="font-mono text-sm text-gray-800 dark:text-gray-300">
          {formattedRemaining}
        </span>
      </div>
      <div
        role="progressbar"
        aria-valuemin={0}
        aria-valuemax={length}
        aria-valuenow={clampedRemaining}
        aria-valuetext={formattedRemaining}
        aria-label={title}
        className={clsx(
          'relative overflow-hidden rounded bg-gray-200 dark:bg-gray-700',
          timerBarSizeStyles(size)
        )}
      >
        <div className={barClasses} style={{ width: `${percent}%` }} />
      </div>
    </div>
  );
};

export default TimerBar;
