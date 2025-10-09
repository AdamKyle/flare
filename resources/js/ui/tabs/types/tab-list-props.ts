import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

export default interface TabsListProps<PTuple extends readonly object[]> {
  tabs: Readonly<TabTupleFromProps<PTuple>>;
  ariaLabel: string;
  activeIndex: number;
  onSelect: (index: number) => void;
  tabIds: string[];
  panelIds: string[];
}
