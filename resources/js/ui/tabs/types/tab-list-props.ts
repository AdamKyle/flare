import React from 'react';

import { TabItem } from 'ui/tabs/types/tab-item';

export default interface TabsListProps {
  tabs: ReadonlyArray<TabItem<React.ComponentType<object>>>;
  ariaLabel: string;
  activeIndex: number;
  onSelect: (index: number) => void;
  tabIds: string[];
  panelIds: string[];
}
