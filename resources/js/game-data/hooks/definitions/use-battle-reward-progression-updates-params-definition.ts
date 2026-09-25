import UseBattleRewardProgressionUpdateStreamResponse from './use-battle-reward-progression-update-stream-response';

export default interface UseBattleRewardProgressionUpdatesParamsDefinition {
  userId: number;
  onEvent: (data: UseBattleRewardProgressionUpdateStreamResponse) => void;
}
