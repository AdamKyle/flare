import { ReactNode } from 'react';

import { TabTuple } from 'ui/tabs/types/tab-item';

export default interface TabsPanelsProps<
  Cs extends readonly ((props: object) => ReactNode)[],
> {
  tabs: Readonly<TabTuple<Cs>>;
  activeIndex: number;
  tabIds: string[];
  panelIds: string[];
}
