import React from 'react';

import ToolTip from 'ui/tool-tips/tool-tip';
import InfoToolTipProps from 'ui/tool-tips/types/info-tool-tip-props';

const InfoToolTip = ({ info_text }: InfoToolTipProps) => {
  return <ToolTip>{info_text}</ToolTip>;
};

export default InfoToolTip;
