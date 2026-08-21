import { match } from 'ts-pattern';

import TimerBarSize from '../enums/timer-bar-size';

export const timerBarSizeStyles = (size: TimerBarSize): string => {
  return match(size)
    .with(TimerBarSize.THIN, () => 'h-2')
    .otherwise(() => 'h-4');
};
