import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { baseStyles } from './styles/button/base-styles';
import { variantStyles } from './styles/button/variant-styles';
import IconButtonProps from './types/icon-button-props';

const IconButton = (props: IconButtonProps): ReactNode => {
  return (
    <button
      onClick={props.on_click}
      className={clsx(
        baseStyles(),
        variantStyles(props.variant),
        'py-3',
        props.additional_css
      )}
      aria-label={props.aria_lebel || props.label || 'Icon Button'}
      disabled={props.disabled}
      role="button"
      type="button"
    >
      <div className="flex flex-col lg:flex-row items-center">
        {props.icon}
        {props.label && (
          <span className="text-sm mt-1 lg:mt-0 lg:ml-1 text-center">
            {props.label}
          </span>
        )}
      </div>
    </button>
  );
};

export default IconButton;
