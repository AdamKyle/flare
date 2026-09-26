import React, { ReactNode } from 'react';

import AutomationStatusTabPanelProps from './types/automation-status-tab-panel-props';

const AutomationStatusTabPanel = ({
  content,
}: AutomationStatusTabPanelProps): ReactNode => {
  return <>{content}</>;
};

export default AutomationStatusTabPanel;
