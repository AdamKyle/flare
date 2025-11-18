import clsx from 'clsx';
import React, { useId, useRef, useState } from 'react';

import { baseStyles } from './styles/button/base-styles';
import { variantStyles } from './styles/button/variant-styles';

import DropdownButtonProps from 'ui/buttons/types/drop-down-button-props';

const DropdownButton = ({
  label,
  variant,
  children,
  on_click,
  disabled,
  additional_css,
  aria_label,
}: DropdownButtonProps) => {
  const [isOpen, setIsOpen] = useState(false);
  const [isAlignedRight, setIsAlignedRight] = useState(false);
  const idBase = useId();
  const buttonRef = useRef<HTMLButtonElement | null>(null);

  const buttonId = `${idBase}-button`;
  const menuId = `${idBase}-menu`;

  const handleToggle = (): void => {
    if (disabled) {
      return;
    }

    if (!isOpen && buttonRef.current && typeof window !== 'undefined') {
      const buttonRect = buttonRef.current.getBoundingClientRect();
      const estimatedMenuWidth = 176;

      const willOverflowRight =
        buttonRect.left + estimatedMenuWidth > window.innerWidth;

      setIsAlignedRight(willOverflowRight);
    }

    setIsOpen((previousIsOpen) => !previousIsOpen);

    if (on_click) {
      on_click();
    }
  };

  const handleMenuClick = (): void => {
    if (!isOpen) {
      return;
    }

    setIsOpen(false);
  };

  const renderButton = () => {
    const iconClassName = clsx(
      'ms-1.5 -me-0.5 h-4 w-4',
      'fas',
      isOpen ? 'fa-chevron-up' : 'fa-chevron-down'
    );

    return (
      <button
        ref={buttonRef}
        id={buttonId}
        onClick={handleToggle}
        className={clsx(
          'box-border inline-flex items-center justify-center border border-transparent',
          baseStyles(),
          variantStyles(variant),
          additional_css
        )}
        aria-label={aria_label || label}
        aria-haspopup="menu"
        aria-expanded={isOpen}
        aria-controls={menuId}
        disabled={disabled}
        role="button"
        type="button"
      >
        <span>{label}</span>
        <i className={iconClassName} aria-hidden="true" />
      </button>
    );
  };

  const renderMenu = () => {
    if (!isOpen) {
      return null;
    }

    const childrenArray = React.Children.toArray(children);

    return (
      <div
        id={menuId}
        className={clsx(
          'absolute top-full z-10 mt-3 w-44 rounded-md border-1',
          'border-gray-100 bg-gray-200 dark:border-gray-500 dark:bg-gray-600',
          isAlignedRight ? 'right-0' : 'left-0'
        )}
        role="menu"
        aria-labelledby={buttonId}
        onClick={handleMenuClick}
      >
        <div className="text-body flex flex-col bg-gray-200 p-2 text-sm font-medium dark:bg-gray-600">
          {childrenArray.map((child, index) => {
            return (
              <React.Fragment key={`dropdown-menu-item-${index}`}>
                {child}
              </React.Fragment>
            );
          })}
        </div>
      </div>
    );
  };

  return (
    <div className="relative inline-flex flex-col">
      {renderButton()}
      {renderMenu()}
    </div>
  );
};

export default DropdownButton;
