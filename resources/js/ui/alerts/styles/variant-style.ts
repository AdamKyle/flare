import { match } from 'ts-pattern';

import { AlertVariant } from 'ui/alerts/enums/alert-variant';

export const variantStyle = (variant: AlertVariant) => {
  return match(variant)
    .with(
      AlertVariant.INFO,
      () => 'border-danube-400 dark:border-danube-500 bg-danube-100'
    )
    .with(
      AlertVariant.SUCCESS,
      () => 'border-emerald-400 dark:border-emerald-400 bg-emerald-100'
    )
    .with(
      AlertVariant.DANGER,
      () => 'border-rose-400 dark:border-rose-500 bg-rose-100'
    )
    .with(
      AlertVariant.WARNING,
      () =>
        'border-mango-tango-400 dark:border-mango-tango-500 bg-mango-tango-100'
    )
    .otherwise(() => '');
};
