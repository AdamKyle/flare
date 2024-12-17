import clsx from 'clsx';
import React from 'react';

import { baseStyle } from 'ui/alerts/styles/base-style';
import { variantStyle } from 'ui/alerts/styles/variant-style';
import AlertProps from 'ui/alerts/types/alert-props';

export const Alert = (props: AlertProps): React.ReactNode => {
  return (
    <div className={clsx(baseStyle(), variantStyle(props.variant))}>
      {props.children}
    </div>
  );
};
