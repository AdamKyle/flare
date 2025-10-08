import { ReactNode } from 'react';

import { TabTuple } from 'ui/tabs/types/tab-item';

export default interface TabsListProps<
  Cs extends readonly ((props: object) => ReactNode)[],
> {
  tabs: Readonly<TabTuple<Cs>>;
  ariaLabel: string;
  activeIndex: number;
  onSelect: (index: number) => void;
  tabIds: string[];
  panelIds: string[];
}
