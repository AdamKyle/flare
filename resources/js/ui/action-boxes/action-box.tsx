import React from 'react';

import ActionBoxBase from 'ui/action-boxes/action-box-base';
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
  const renderLoadingIcon = () => {
    if (!is_loading) {
      return null;
    }

    return <i className="fas fa-spinner fa-spin" aria-hidden="true"></i>;
  };

  const renderActions = () => {
    return (
      <>
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
      </>
    );
  };

  return (
    <ActionBoxBase
      variant={variant}
      actions={renderActions()}
      additional_css={additional_css}
    >
      <div className={'grid grid-cols-2 items-stretch gap-2'}>{children}</div>
    </ActionBoxBase>
  );
};

export default ActionBox;
