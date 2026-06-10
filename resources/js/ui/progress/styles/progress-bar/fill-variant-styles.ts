import { match } from 'ts-pattern';

import { ProgressBarVariant } from '../../enums/progress-bar-variant';

export const fillVariantStyles = (variant: ProgressBarVariant): string => {
  return match(variant)
    .with(ProgressBarVariant.PRIMARY, () => 'bg-danube-500 dark:bg-danube-300')
    .with(
      ProgressBarVariant.SUMMER,
      () => 'bg-mango-tango-500 dark:bg-mango-tango-300'
    )
    .with(
      ProgressBarVariant.PINK_MOON,
      () => 'bg-wisp-pink-500 dark:bg-wisp-pink-300'
    )
    .with(ProgressBarVariant.ARTIC, () => 'bg-glacier-500 dark:bg-glacier-300')
    .otherwise(() => '');
};
