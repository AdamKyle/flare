import React from 'react';

import StatInfoToolTip from '../item/stat-info-tool-tip';
import InfoLabelProps from './types/info-label-props';

const InfoLabel = ({
  label,
  tooltip,
  tooltipValue = 0,
  tooltipAlign = 'right',
  tooltipRenderAsPercent,
  tooltipSize = 'sm',
}: InfoLabelProps) => {
  return (
    <span className="inline-flex items-center gap-2">
      {tooltip ? (
        <StatInfoToolTip
          label={tooltip}
          value={tooltipValue}
          align={tooltipAlign}
          renderAsPercent={tooltipRenderAsPercent}
          size={tooltipSize}
          custom_message
        />
      ) : null}
      <span className="min-w-0 break-words">{label}</span>
    </span>
  );
};

export default InfoLabel;
