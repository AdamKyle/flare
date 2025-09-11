import { ReactNode } from 'react';

import { ActionBoxVariant } from 'ui/action-boxes/enums/action-box-varient';

export default interface ActionBoxProps {
  variant: ActionBoxVariant;
  on_submit: () => void;
  on_close: () => void;
  children: ReactNode;
  additional_css?: string;
  is_loading?: boolean;
}
