import clsx from 'clsx';
import React, { useEffect, useState } from 'react';

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
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
      <button type="button" onClick={handleClose} className="ml-4">
        <i className="fas fa-times" />
      </button>
    );
  };

  const renderAlert = () => {
    if (!visible) {
      return null;
    }

    return (
      <div
        className={clsx(
          baseStyle(),
          variantStyle(props.variant),
          'flex justify-between items-start'
        )}
      >
        <div className="flex-1">{props.children}</div>
        {renderCloseButton()}
      </div>
    );
  };

  return <>{renderAlert()}</>;
};
