export default interface UseBattleRewardProgressionUpdateStreamResponse {
  requestId: number;
  level: number | null;
  xp: number | null;
  xpNext: number | null;
  complete: boolean;
}
