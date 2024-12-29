import { match } from 'ts-pattern';

import { ButtonVariant } from '../../enums/button-variant-enum';

export const progressVariantStyles = (variant: ButtonVariant): string => {
  return match(variant)
    .with(ButtonVariant.DANGER, () => 'bg-rose-600 hover:bg-rose-500')
    .with(ButtonVariant.SUCCESS, () => 'bg-emerald-600 hover:bg-emerald-500')
    .with(ButtonVariant.PRIMARY, () => 'bg-danube-600 hover:bg-danube-500')
    .otherwise(() => '');
};
