import { match } from 'ts-pattern';

import { ProgressBarVariant } from '../../enums/progress-bar-variant';

export const trackVariantStyles = (variant: ProgressBarVariant): string => {
  return match(variant)
    .with(ProgressBarVariant.PRIMARY, () => 'bg-danube-100 dark:bg-danube-600')
    .with(
      ProgressBarVariant.SUMMER,
      () => 'bg-mango-tango-100 dark:bg-mango-tango-600'
    )
    .with(
      ProgressBarVariant.PINK_MOON,
      () => 'bg-wisp-pink-100 dark:bg-wisp-pink-600'
    )
    .with(ProgressBarVariant.ARTIC, () => 'bg-glacier-100 dark:bg-glacier-600')
    .otherwise(() => '');
};
