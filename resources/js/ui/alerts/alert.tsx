import clsx from 'clsx';
import React, { useEffect, useState } from 'react';

import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { baseStyle } from 'ui/alerts/styles/base-style';
import { variantStyle } from 'ui/alerts/styles/variant-style';
import AlertProps from 'ui/alerts/types/alert-props';

export const Alert = ({
  variant,
  children,
  closable,
  on_close,
  force_close,
}: AlertProps) => {
  const [visible, setVisible] = useState(true);

  const isDanger = variant === AlertVariant.DANGER;

  useEffect(() => {
    if (force_close) {
      setVisible(false);

      if (on_close) {
        on_close();
      }

      return;
    }

    setVisible(true);
  }, [force_close, on_close, children]);

  const handleClose = (): void => {
    setVisible(false);

    if (on_close) {
      on_close();
    }
  };

  const renderCloseButton = () => {
    if (!closable) {
      return null;
    }

    return (
      <button
        type="button"
        aria-label="Close alert"
        onClick={handleClose}
        className="focus:ring-danube-500 dark:focus:ring-danube-300 ml-4 rounded p-1 focus:ring-2 focus:outline-none"
      >
        <i className="fas fa-times" aria-hidden="true" />
      </button>
    );
  };

  const renderAlert = () => {
    if (!visible) {
      return null;
    }

    return (
      <div
        role={isDanger ? 'alert' : 'status'}
        aria-live={isDanger ? 'assertive' : 'polite'}
        aria-atomic="true"
        className={clsx(
          baseStyle(),
          variantStyle(variant),
          'flex items-start justify-between'
        )}
      >
        <div className="flex-1">{children}</div>
        {renderCloseButton()}
      </div>
    );
  };

  return <>{renderAlert()}</>;
};
