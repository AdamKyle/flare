export default interface UseUnreadBadgesDefinition {
  unreadServer: boolean;
  unreadAnnouncements: boolean;
  activeTabIndex: number;
  handleActiveIndexChange: (index: number) => void;
}
