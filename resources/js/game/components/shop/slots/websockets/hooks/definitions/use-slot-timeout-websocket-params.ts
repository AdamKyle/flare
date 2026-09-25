export default interface UseSlotTimeoutWebsocketParams {
  user_id: number;
  enabled: boolean;
  on_timeout_update: (timeoutFor: number) => void;
}
