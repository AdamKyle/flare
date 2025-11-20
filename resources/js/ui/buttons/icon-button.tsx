import clsx from 'clsx';
import React from 'react';

import { baseStyles } from './styles/button/base-styles';
import { variantStyles } from './styles/button/variant-styles';
import IconButtonProps from './types/icon-button-props';

const IconButton = ({
  on_click,
  icon,
  variant,
  label,
  disabled,
  additional_css,
  aria_label,
  center_content,
}: IconButtonProps) => {
  const hasIcon = Boolean(icon);

  const renderIcon = () => {
    if (!icon) {
      return null;
    }

    return (
      <span className="inline-flex w-4 shrink-0 justify-center">{icon}</span>
    );
  };

  const renderLabel = () => {
    if (!label) {
      return null;
    }

    const labelClassName = clsx(
      'text-sm',
      center_content ? 'text-center' : 'text-left'
    );

    return <span className={labelClassName}>{label}</span>;
  };

  const renderContent = () => {
    const contentClassName = clsx(
      'flex items-center',
      hasIcon && 'gap-2',
      center_content && 'justify-center'
    );

    return (
      <div className={contentClassName}>
        {renderIcon()}
        {renderLabel()}
      </div>
    );
  };

  return (
    <button
      onClick={on_click}
      className={clsx(
        baseStyles(),
        variantStyles(variant),
        'py-3',
        additional_css
      )}
      aria-label={aria_label || label || 'Icon Button'}
      disabled={disabled}
      role="button"
      type="button"
    >
      {renderContent()}
    </button>
  );
};

export default IconButton;
