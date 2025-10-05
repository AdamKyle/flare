import clsx from 'clsx';
import React from 'react';

import {
  borderStyle,
  bottomBgStyle,
  topBgStyle,
} from 'ui/action-boxes/styles/action-boxes-styles';
import ActionBoxBaseProps from 'ui/action-boxes/types/action-box-base-props';

const ActionBox = ({
  variant,
  children,
  additional_css,
  actions,
}: ActionBoxBaseProps) => {
  const containerClass = clsx(
    'w-full rounded-md overflow-hidden border-2',
    borderStyle(variant),
    'text-gray-900 dark:text-gray-100'
  );

  const topClass = clsx('p-4', topBgStyle(variant));

  const bottomClass = clsx('p-4', bottomBgStyle(variant));

  return (
    <div className={clsx(containerClass, additional_css)}>
      <div className={topClass}>{children}</div>

      <div className={bottomClass}>{actions}</div>
    </div>
  );
};

export default ActionBox;
