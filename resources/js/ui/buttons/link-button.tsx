import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { baseStyles } from './styles/link-buttons/base-styles';
import { variantStyles } from './styles/link-buttons/variant-styles';
import LinkButtonProps from './types/link-button-props';

const LinkButton = (props: LinkButtonProps): ReactNode => {
  return (
    <button
      type="button"
      onClick={props.on_click}
      className={clsx(
        baseStyles(),
        variantStyles(props.variant),
        props.additional_css
      )}
      disabled={props.disabled}
      aria-label={props.aria_label}
      role="button"
    >
      {props.label}
    </button>
  );
};

export default LinkButton;
