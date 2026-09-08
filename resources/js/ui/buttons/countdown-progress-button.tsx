import React, { ReactNode, useEffect, useRef, useState } from 'react';

import ProgressButton from 'ui/buttons/button-progress';
import CountdownProgressButtonProps from 'ui/buttons/types/countdown-progress-button-props';
import {
  formatDetailedRemainingTime,
  formatRemainingTime,
} from 'ui/timer-bar/util/format-remaining-time';

const resolveRemainingSeconds = (completeAt: string): number => {
  const remainingMs = new Date(completeAt).getTime() - Date.now();

  return Math.max(0, Math.round(remainingMs / 1000));
};

const CountdownProgressButton = (
  props: CountdownProgressButtonProps
): ReactNode => {
  const [remainingSeconds, setRemainingSeconds] = useState(() =>
    resolveRemainingSeconds(props.complete_at)
  );

  const hasCompletedRef = useRef(false);

  useEffect(() => {
    hasCompletedRef.current = false;
    setRemainingSeconds(resolveRemainingSeconds(props.complete_at));

    const intervalId = setInterval(() => {
      setRemainingSeconds(resolveRemainingSeconds(props.complete_at));
    }, 1000);

    return () => {
      clearInterval(intervalId);
    };
  }, [props.complete_at]);

  useEffect(() => {
    if (remainingSeconds > 0 || hasCompletedRef.current) {
      return;
    }

    hasCompletedRef.current = true;
    props.on_complete?.();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [remainingSeconds]);

  const totalSeconds = Math.max(
    0,
    Math.round(
      (new Date(props.complete_at).getTime() -
        new Date(props.started_at).getTime()) /
        1000
    )
  );

  const progress =
    totalSeconds > 0
      ? Math.min(100, Math.max(0, (remainingSeconds / totalSeconds) * 100))
      : 0;

  const label = `${props.label_prefix} — ${
    props.detailed_time
      ? formatDetailedRemainingTime(remainingSeconds)
      : formatRemainingTime(remainingSeconds)
  }`;

  return (
    <ProgressButton
      label={label}
      progress={progress}
      variant={props.variant}
      on_click={props.on_click}
      disabled={props.disabled}
      additional_css={props.additional_css}
      announce_progress={false}
    />
  );
};

export default CountdownProgressButton;
