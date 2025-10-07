import React from 'react';

import { TabItem } from 'ui/tabs/types/tab-item';

export default interface TabsPanelsProps {
  tabs: ReadonlyArray<TabItem<React.ComponentType<object>>>;
  activeIndex: number;
  tabIds: string[];
  panelIds: string[];
}
