import React, { useId } from 'react';

import BaseToolTip from './base-tool-tip';

import GeneralToolTipProps from 'ui/tool-tips/types/general-tool-tip-props';

const GeneralToolTip = (props: GeneralToolTipProps) => {
  const {
    label,
    message,
    align = 'right',
    size = 'sm',
    is_open,
    on_open,
    on_close,
  } = props;

  const localId = useId();
  const tooltipId = `general-info-${label.replace(/\s+/g, '-').toLowerCase()}-${localId}`;

  const getMessage = (): string | React.ReactNode => {
    if (typeof message !== 'undefined') {
      return message;
    }

    return label;
  };

  return (
    <BaseToolTip
      tooltipId={tooltipId}
      label={label}
      align={align}
      size={size}
      is_open={is_open}
      on_open={on_open}
      on_close={on_close}
      content={getMessage()}
      placementDeps={[label]}
    />
  );
};

export default GeneralToolTip;
