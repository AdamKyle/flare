import { ReactNode } from 'react';

import { TabTuple } from 'ui/tabs/types/tab-item';

export default interface PillTabsProps<
  Cs extends readonly ((props: object) => ReactNode)[],
> {
  tabs: Readonly<TabTuple<Cs>>;
  ariaLabel?: string;
  initialIndex?: number;
}
