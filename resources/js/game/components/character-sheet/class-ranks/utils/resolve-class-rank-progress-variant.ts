import { match } from 'ts-pattern';

import { ClassRankVisualState } from '../enums/class-rank-visual-state';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';

export const resolveClassRankProgressVariant = (
  visualState: ClassRankVisualState
): ProgressBarVariant => {
  return match(visualState)
    .with(ClassRankVisualState.CURRENT, () => ProgressBarVariant.PRIMARY)
    .with(ClassRankVisualState.MASTERED, () => ProgressBarVariant.DE_YORK)
    .with(ClassRankVisualState.UNLOCKED, () => ProgressBarVariant.ARTIC)
    .otherwise(() => ProgressBarVariant.SUMMER);
};
