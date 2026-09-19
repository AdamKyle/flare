import React, { ReactNode, useEffect, useState } from 'react';

import AttackCooldownTimerProps from './types/attack-cooldown-timer-props';

import TimerBar from 'ui/timer-bar/timer-bar';

const resolveRemaining = (endsAt: number): number =>
  Math.max(0, Math.round((endsAt - Date.now()) / 100) / 10);

/**
 * Owns the 100ms cooldown tick locally so only this component rerenders on
 * each tick, instead of the parent MonsterSection and everything it renders.
 */
const AttackCooldownTimer = ({
  cooldown_seconds: cooldownSeconds,
}: AttackCooldownTimerProps): ReactNode => {
  const [remaining, setRemaining] = useState(cooldownSeconds);

  useEffect(() => {
    const endsAt = Date.now() + cooldownSeconds * 1000;

    setRemaining(resolveRemaining(endsAt));

    if (cooldownSeconds <= 0) {
      return;
    }

    const intervalId = setInterval(() => {
      const next = resolveRemaining(endsAt);

      setRemaining(next);

      if (next <= 0) {
        clearInterval(intervalId);
      }
    }, 100);

    return () => {
      clearInterval(intervalId);
    };
  }, [cooldownSeconds]);

  return (
    <TimerBar
      length={cooldownSeconds}
      remaining={remaining}
      precise_time
      title="Next Attack"
      additional_css="my-2"
    />
  );
};

export default AttackCooldownTimer;
