import { ReactNode } from 'react';

import { ActionBoxVariant } from 'ui/action-boxes/enums/action-box-varient';

export default interface ActionBoxBaseProps {
  variant: ActionBoxVariant;
  children: ReactNode;
  additional_css?: string;
  actions: ReactNode;
}
