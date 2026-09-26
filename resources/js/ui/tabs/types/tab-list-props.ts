import { TabItemPresentation } from 'ui/tabs/types/tab-item';

export default interface TabsListProps {
  tabs: readonly TabItemPresentation[];
  ariaLabel: string;
  activeIndex: number;
  onSelect: (index: number) => void;
  tabIds: string[];
  panelIds: string[];
  additional_tab_css?: string;
}
