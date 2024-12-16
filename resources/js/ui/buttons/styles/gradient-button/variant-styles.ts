import { match } from 'ts-pattern';

import { ButtonGradientVarient } from '../../enums/button-gradient-variant';

export const variantStyles = (variant: ButtonGradientVarient): string => {
  return match(variant)
    .with(
      ButtonGradientVarient.DANGER_TO_PRIMARY,
      () =>
        'bg-gradient-to-b from-rose-600 to-danube-600 hover:from-rose-500 hover:to-danube-500 focus:ring-rose-400 dark:focus:ring-danube-600'
    )
    .with(
      ButtonGradientVarient.PRIMARY_TO_DANGER,
      () =>
        'bg-gradient-to-b from-danube-600 to-rose-600 hover:from-danube-500 hover:to-rose-500 focus:ring-danube-400 dark:focus:ring-danube-600'
    )
    .otherwise(() => '');
};
