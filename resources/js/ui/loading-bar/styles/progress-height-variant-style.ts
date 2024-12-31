import { match } from 'ts-pattern';

import { ProgressBarHeightVariant } from 'ui/loading-bar/enums/progress-bar-height-variant';

export const progressHeightVariantStyle = (
  variant: ProgressBarHeightVariant
): string => {
  return match(variant)
    .with(ProgressBarHeightVariant.SMALL, () => 'h-1.5')
    .with(ProgressBarHeightVariant.MEDIUM, () => 'h-2.5')
    .with(ProgressBarHeightVariant.LARGE, () => 'h-3.5')
    .otherwise(() => 'h-1.5');
};
