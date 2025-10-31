export default interface UseEmitMapRefreshDefinition {
  shouldRefreshMap: boolean;
  emitShouldRefreshMap: (shouldRefresh: boolean) => void;
}
