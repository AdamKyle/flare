export default interface UseUnreadBadgesDefinition {
  unreadServer: boolean;
  activeTabIndex: number;
  handleActiveIndexChange: (index: number) => void;
}
