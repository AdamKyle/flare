import { match } from 'ts-pattern';

import { ProgressBarVariant } from '../../enums/progress-bar-variant';

export const trackVariantStyles = (variant: ProgressBarVariant): string => {
  return match(variant)
    .with(
      ProgressBarVariant.PRIMARY,
      () => 'bg-danube-100 dark:bg-danube-900/60'
    )
    .with(
      ProgressBarVariant.SUMMER,
      () => 'bg-mango-tango-100 dark:bg-mango-tango-900/60'
    )
    .with(
      ProgressBarVariant.PINK_MOON,
      () => 'bg-wisp-pink-100 dark:bg-wisp-pink-900/60'
    )
    .with(
      ProgressBarVariant.ARTIC,
      () => 'bg-glacier-100 dark:bg-glacier-900/60'
    )
    .with(
      ProgressBarVariant.DE_YORK,
      () => 'bg-de-york-100 dark:bg-de-york-900/60'
    )
    .otherwise(() => '');
};
