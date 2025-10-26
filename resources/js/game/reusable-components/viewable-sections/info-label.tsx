import React from 'react';

import InfoLabelProps from './types/info-label-props';
import StatToolTip from '../item/tool-tips/stat-tool-tip';

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
        <StatToolTip
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
