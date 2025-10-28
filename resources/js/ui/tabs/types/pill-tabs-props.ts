import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

export default interface PillTabsProps<PTuple extends readonly object[]> {
  tabs: Readonly<TabTupleFromProps<PTuple>>;
  ariaLabel?: string;
  initialIndex?: number;
  additional_tab_css?: string;
}
