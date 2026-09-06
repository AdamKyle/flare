import { PillTabsAlignment } from 'ui/tabs/enums/pill-tabs-alignment';
import { TabTupleFromProps } from 'ui/tabs/types/tab-item';

export default interface PillTabsProps<PTuple extends readonly object[]> {
  tabs: Readonly<TabTupleFromProps<PTuple>>;
  ariaLabel?: string;
  initialIndex?: number;
  activeIndex?: number;
  additional_tab_css?: string;
  onActiveIndexChange?: (index: number) => void;
  alignment?: PillTabsAlignment;
}
