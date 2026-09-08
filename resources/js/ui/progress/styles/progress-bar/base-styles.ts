import { match } from 'ts-pattern';

import { ProgressBarSize } from '../../enums/progress-bar-size';

export const baseTrackStyles = (size: ProgressBarSize): string => {
  return match(size)
    .with(ProgressBarSize.THIN, () => 'w-full h-1 rounded-full')
    .otherwise(() => 'w-full h-3 rounded-full');
};

export const baseFillStyles = (): string => {
  return 'h-full rounded-full transition-all';
};
