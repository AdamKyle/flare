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
      <button type="button" onClick={handleClose} className="ml-4">
        <i className="fas fa-times" />
      </button>
    );
  };

  return (
    <>
      {visible && (
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
      )}
    </>
  );
};
