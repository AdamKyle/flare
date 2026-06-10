export default interface UseCraftingTimeoutWebsocketParams {
  userId: number;
  onTimeoutUpdate: (timeout: number | null) => void;
}
