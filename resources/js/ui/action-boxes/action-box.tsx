import clsx from 'clsx';
import React from 'react';

import {
  borderStyle,
  bottomBgStyle,
  topBgStyle,
} from 'ui/action-boxes/styles/action-boxes-styles';
import ActionBoxProps from 'ui/action-boxes/types/action-box-props';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const ActionBox = ({
  variant,
  on_submit,
  on_close,
  children,
  additional_css,
  is_loading,
}: ActionBoxProps) => {
  const containerClass = clsx(
    'w-full rounded-md overflow-hidden border-2',
    borderStyle(variant),
    'text-gray-900 dark:text-gray-100'
  );

  const topClass = clsx('p-4', topBgStyle(variant));

  const bottomClass = clsx(
    'p-4 grid grid-cols-2 gap-2 items-stretch',
    bottomBgStyle(variant)
  );

  const renderLoadingIcon = () => {
    if (!is_loading) {
      return null;
    }

    return <i className="fas fa-spinner fa-spin" aria-hidden="true"></i>;
  };

  return (
    <div className={clsx(containerClass, additional_css)}>
      <div className={topClass}>{children}</div>

      <div className={bottomClass}>
        <IconButton
          disabled={is_loading}
          on_click={on_submit}
          label="Yes, I am sure"
          variant={ButtonVariant.SUCCESS}
          additional_css="w-full [&>div]:justify-center [&>div]:gap-2"
          icon={renderLoadingIcon()}
        />

        <Button
          disabled={is_loading}
          on_click={on_close}
          label="Cancel"
          variant={ButtonVariant.DANGER}
          additional_css="w-full justify-center"
        />
      </div>
    </div>
  );
};

export default ActionBox;
