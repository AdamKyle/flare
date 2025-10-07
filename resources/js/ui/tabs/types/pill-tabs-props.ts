import React from 'react';

import { TabItem } from 'ui/tabs/types/tab-item';

export default interface PillTabsProps {
  tabs: ReadonlyArray<TabItem<React.ComponentType<object>>>;
  ariaLabel?: string;
  initialIndex?: number;
}
