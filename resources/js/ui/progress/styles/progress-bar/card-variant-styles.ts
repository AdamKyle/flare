import { match } from 'ts-pattern';

import { ProgressBarVariant } from '../../enums/progress-bar-variant';

export const cardSurfaceVariantStyles = (
  variant: ProgressBarVariant
): string => {
  return match(variant)
    .with(
      ProgressBarVariant.PRIMARY,
      () =>
        'border-danube-300 dark:border-danube-500 bg-danube-50 dark:bg-danube-900 hover:bg-danube-100 dark:hover:bg-danube-800'
    )
    .with(
      ProgressBarVariant.SUMMER,
      () =>
        'border-mango-tango-400 dark:border-mango-tango-500 bg-mango-tango-100 dark:bg-mango-tango-900 hover:bg-mango-tango-200 dark:hover:bg-mango-tango-800'
    )
    .with(
      ProgressBarVariant.PINK_MOON,
      () =>
        'border-wisp-pink-400 dark:border-wisp-pink-500 bg-wisp-pink-100 dark:bg-wisp-pink-900 hover:bg-wisp-pink-200 dark:hover:bg-wisp-pink-800'
    )
    .with(
      ProgressBarVariant.ARTIC,
      () =>
        'border-glacier-400 dark:border-glacier-500 bg-glacier-100 dark:bg-glacier-900 hover:bg-glacier-200 dark:hover:bg-glacier-800'
    )
    .with(
      ProgressBarVariant.DE_YORK,
      () =>
        'border-de-york-400 dark:border-de-york-500 bg-de-york-100 dark:bg-de-york-900 hover:bg-de-york-200 dark:hover:bg-de-york-800'
    )
    .otherwise(() => '');
};

export const cardTextVariantStyles = (variant: ProgressBarVariant): string => {
  return match(variant)
    .with(
      ProgressBarVariant.PRIMARY,
      () => 'text-danube-900 dark:text-danube-100'
    )
    .with(
      ProgressBarVariant.SUMMER,
      () => 'text-mango-tango-900 dark:text-mango-tango-100'
    )
    .with(
      ProgressBarVariant.PINK_MOON,
      () => 'text-wisp-pink-900 dark:text-wisp-pink-100'
    )
    .with(
      ProgressBarVariant.ARTIC,
      () => 'text-glacier-900 dark:text-glacier-100'
    )
    .with(
      ProgressBarVariant.DE_YORK,
      () => 'text-de-york-900 dark:text-de-york-100'
    )
    .otherwise(() => '');
};

export const cardMutedTextVariantStyles = (
  variant: ProgressBarVariant
): string => {
  return match(variant)
    .with(
      ProgressBarVariant.PRIMARY,
      () => 'text-danube-800 dark:text-danube-200'
    )
    .with(
      ProgressBarVariant.SUMMER,
      () => 'text-mango-tango-800 dark:text-mango-tango-200'
    )
    .with(
      ProgressBarVariant.PINK_MOON,
      () => 'text-wisp-pink-800 dark:text-wisp-pink-200'
    )
    .with(
      ProgressBarVariant.ARTIC,
      () => 'text-glacier-800 dark:text-glacier-200'
    )
    .with(
      ProgressBarVariant.DE_YORK,
      () => 'text-de-york-800 dark:text-de-york-200'
    )
    .otherwise(() => '');
};
