import clsx from 'clsx';
import React, { useEffect, useState } from 'react';

import TimerBarSize from 'ui/timer-bar/enums/timer-bar-size';
import { timerBarSizeStyles } from 'ui/timer-bar/styles/timer-bar-size-styles';
import TimerBarProps from 'ui/timer-bar/types/timer-bar-props';
import {
  formatDetailedRemainingTime,
  formatRemainingTime,
} from 'ui/timer-bar/util/format-remaining-time';
import { getColorLevel } from 'ui/timer-bar/util/get-color-level';

const resolveRemainingFromCompleteAt = (completeAt: string): number => {
  const remainingMs = new Date(completeAt).getTime() - Date.now();

  return Math.max(0, Math.round(remainingMs / 1000));
};

const TimerBar = ({
  length,
  title,
  remaining: controlledRemaining,
  complete_at: completeAt,
  detailed_time: detailedTime = false,
  size = TimerBarSize.DEFAULT,
  additional_css,
}: TimerBarProps) => {
  const [internalRemaining, setInternalRemaining] = useState(length);
  const [completeAtRemaining, setCompleteAtRemaining] = useState(() =>
    completeAt ? resolveRemainingFromCompleteAt(completeAt) : 0
  );

  const isCompleteAtControlled = Boolean(completeAt);
  const isControlled =
    !isCompleteAtControlled && controlledRemaining !== undefined;

  useEffect(() => {
    if (!completeAt) {
      return;
    }

    setCompleteAtRemaining(resolveRemainingFromCompleteAt(completeAt));

    const timerId = setInterval(() => {
      setCompleteAtRemaining(resolveRemainingFromCompleteAt(completeAt));
    }, 1000);

    return () => {
      clearInterval(timerId);
    };
  }, [completeAt]);

  useEffect(() => {
    if (isControlled || isCompleteAtControlled) {
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
  }, [isControlled, isCompleteAtControlled, length]);

  const displayedRemaining = isCompleteAtControlled
    ? completeAtRemaining
    : isControlled
      ? (controlledRemaining as number)
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

  const formattedRemaining = detailedTime
    ? formatDetailedRemainingTime(clampedRemaining)
    : formatRemainingTime(clampedRemaining);

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
