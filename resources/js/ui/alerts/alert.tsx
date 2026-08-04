import clsx from 'clsx';
import React, { useEffect, useState } from 'react';

import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { baseStyle } from 'ui/alerts/styles/base-style';
import { variantStyle } from 'ui/alerts/styles/variant-style';
import AlertProps from 'ui/alerts/types/alert-props';

export const Alert = (props: AlertProps) => {
  const [visible, setVisible] = useState(true);

  useEffect(() => {
    if (props.force_close) {
      setVisible(false);

      if (props.on_close) {
        props.on_close();
      }

      return;
    }

    setVisible(true);
  }, [props.force_close, props.on_close, props.children]);

  const handleClose = (): void => {
    setVisible(false);

    if (props.on_close) {
      props.on_close();
    }
  };

  const renderCloseButton = () => {
    if (!props.closable) {
      return null;
    }

    return (
      <button
        type="button"
        aria-label="Close alert"
        onClick={handleClose}
        className="ml-4 rounded p-1 focus:ring-2 focus:ring-blue-500 focus:outline-none"
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
        role={props.variant === AlertVariant.DANGER ? 'alert' : 'status'}
        aria-live={
          props.variant === AlertVariant.DANGER ? 'assertive' : 'polite'
        }
        aria-atomic="true"
        className={clsx(
          baseStyle(),
          variantStyle(props.variant),
          'flex items-start justify-between'
        )}
      >
        <div className="flex-1">{props.children}</div>
        {renderCloseButton()}
      </div>
    );
  };

  return <>{renderAlert()}</>;
};
