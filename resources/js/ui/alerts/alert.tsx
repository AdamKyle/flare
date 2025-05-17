// ui/alerts/Alert.tsx
import clsx from 'clsx';
import React, { useState } from 'react';

import { baseStyle } from 'ui/alerts/styles/base-style';
import { variantStyle } from 'ui/alerts/styles/variant-style';
import AlertProps from 'ui/alerts/types/alert-props';

export const Alert = (props: AlertProps): React.ReactNode => {
  const [visible, setVisible] = useState(true);

  const handleClose = (): void => {
    setVisible(false);
    if (props.on_close) {
      props.on_close();
    }
  };

  const renderCloseButton = (): React.ReactNode => {
    if (!props.closable) {
      return null;
    }

    return (
      <button type="button" className="alert-close" onClick={handleClose}>
        <i className="fas fa-times" />
      </button>
    );
  };

  return (
    <>
      {visible && (
        <div className={clsx(baseStyle(), variantStyle(props.variant))}>
          {props.children}
          {renderCloseButton()}
        </div>
      )}
    </>
  );
};
