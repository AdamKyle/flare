import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { baseStyles } from './styles/button/base-styles';
import { variantStyles } from './styles/button/variant-styles';
import IconButtonProps from './types/icon-button-props';

const IconButton = (props: IconButtonProps): ReactNode => {
  const hasIcon = Boolean(props.icon);

  return (
    <button
      onClick={props.on_click}
      className={clsx(
        baseStyles(),
        variantStyles(props.variant),
        'py-3',
        props.additional_css
      )}
      aria-label={props.aria_label || props.label || 'Icon Button'}
      disabled={props.disabled}
      role="button"
      type="button"
    >
      <div className={clsx('flex items-center', hasIcon && 'gap-2')}>
        {hasIcon && (
          <span className="inline-flex w-4 shrink-0 justify-center">
            {props.icon}
          </span>
        )}
        {props.label && (
          <span className="text-left text-sm">{props.label}</span>
        )}
      </div>
    </button>
  );
};

export default IconButton;
