import { match } from 'ts-pattern';

import { ButtonVariant } from '../../enums/button-variant-enum';

export const variantStyles = (variant: ButtonVariant): string => {
  return match(variant)
    .with(
      ButtonVariant.DANGER,
      () =>
        'text-rose-600 dark:text-rose-300 hover:text-rose-500 dark:hover:text-rose-200 focus:ring-rose-400 dark:focus:ring-rose-200'
    )
    .with(
      ButtonVariant.SUCCESS,
      () =>
        'text-emerald-600 dark:text-emerald-300 hover:text-emerald-500 dark:hover:text-emerald-200 focus:ring-emerald-400 dark:focus:ring-emerald-200'
    )
    .with(
      ButtonVariant.PRIMARY,
      () =>
        'text-danube-600 dark:text-danube-300 hover:text-danube-500 dark:hover:text-danube-200 focus:ring-danube-400 dark:focus:ring-danube-200'
    )
    .otherwise(() => '');
};
