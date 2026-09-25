export default interface UseUnreadBadgesDefinition {
  unreadServer: boolean;
  unreadExploration: boolean;
  activeTabIndex: number;
  handleActiveIndexChange: (index: number) => void;
}
