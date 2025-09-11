import { match } from 'ts-pattern';

import { ActionBoxVariant } from 'ui/action-boxes/enums/action-box-varient';

export const borderStyle = (variant: ActionBoxVariant): string => {
  return match(variant)
    .with(ActionBoxVariant.DANGER, () => 'border-rose-500 dark:border-rose-400')
    .with(
      ActionBoxVariant.SUCCESS,
      () => 'border-emerald-500 dark:border-emerald-500'
    )
    .with(
      ActionBoxVariant.PRIMARY,
      () => 'border-danube-500 dark:border-danube-500'
    )
    .otherwise(() => 'border-gray-300 dark:border-gray-600');
};

export const topBgStyle = (variant: ActionBoxVariant): string => {
  return match(variant)
    .with(ActionBoxVariant.DANGER, () => 'bg-rose-100 dark:bg-rose-300')
    .with(ActionBoxVariant.SUCCESS, () => 'bg-emerald-100 dark:bg-emerald-700')
    .with(ActionBoxVariant.PRIMARY, () => 'bg-danube-100 dark:bg-danube-700')
    .otherwise(() => 'bg-gray-50 dark:bg-gray-700');
};

export const bottomBgStyle = (variant: ActionBoxVariant): string => {
  return match(variant)
    .with(ActionBoxVariant.DANGER, () => 'bg-rose-200 dark:bg-rose-400')
    .with(ActionBoxVariant.SUCCESS, () => 'bg-emerald-200 dark:bg-emerald-800')
    .with(ActionBoxVariant.PRIMARY, () => 'bg-danube-200 dark:bg-danube-800')
    .otherwise(() => 'bg-gray-100 dark:bg-gray-800');
};
