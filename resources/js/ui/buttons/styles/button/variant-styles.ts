import { match } from 'ts-pattern';

import { ButtonVariant } from '../../enums/button-variant-enum';

export const variantStyles = (variant: ButtonVariant): string => {
  return match(variant)
    .with(
      ButtonVariant.DANGER,
      () =>
        'bg-rose-600 hover:bg-rose-500 focus:ring-rose-400 dark:focus:ring-rose-600'
    )
    .with(
      ButtonVariant.SUCCESS,
      () =>
        'bg-emerald-600 hover:bg-emerald-500 focus:ring-emerald-400 dark:focus:ring-emerald-600'
    )
    .with(
      ButtonVariant.PRIMARY,
      () =>
        'bg-danube-600 hover:bg-danube-500 focus:ring-danube-400 dark:focus:ring-danube-600'
    )
    .otherwise(() => '');
};
