import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

export default interface TabsPanelsProps<PTuple extends readonly object[]> {
  tabs: Readonly<TabTupleFromProps<PTuple>>;
  activeIndex: number;
  tabIds: string[];
  panelIds: string[];
}
