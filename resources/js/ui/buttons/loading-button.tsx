import React, { ReactNode } from 'react';

import IconButton from './icon-button';
import LoadingButtonProps from './types/loading-button-props';

const LoadingButton = (props: LoadingButtonProps): ReactNode => {
  const renderIcon = (): ReactNode => {
    if (!props.is_loading) {
      return null;
    }

    return <i className="fas fa-spinner fa-spin" aria-hidden="true"></i>;
  };

  const visibleLabel = props.is_loading ? props.loading_label : props.label;

  return (
    <IconButton
      on_click={props.on_click}
      variant={props.variant}
      icon={renderIcon()}
      label={visibleLabel}
      disabled={props.disabled || props.is_loading}
      aria_busy={props.is_loading}
      aria_label={
        props.is_loading
          ? props.loading_label
          : (props.aria_label ?? props.label)
      }
      additional_css={props.additional_css}
      center_content
    />
  );
};

export default LoadingButton;
