import { match } from 'ts-pattern';

import { ButtonVariant } from '../../enums/button-variant-enum';

export const variantStyles = (variant: ButtonVariant): string => {
  return match(variant)
    .with(
      ButtonVariant.DANGER,
      () =>
        'text-rose-600 dark:text-rose-500 hover:text-rose-500 dark:hover:text-rose-400 focus:ring-rose-400 dark:focus:ring-rose-600'
    )
    .with(
      ButtonVariant.SUCCESS,
      () =>
        'text-emerald-600 dark:text-emerald-500 hover:text-emerald-500 dark:hover:text-emerald-400 focus:ring-emerald-400 dark:focus:ring-emerald-600'
    )
    .with(
      ButtonVariant.PRIMARY,
      () =>
        'text-danube-600 dark:text-danbue-500 hover:text-danube-500 dark:hover:text-danbue-400 focus:ring-danube-400 dark:focus:ring-danube-600'
    )
    .otherwise(() => '');
};
