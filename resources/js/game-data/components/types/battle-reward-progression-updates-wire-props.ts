import UseBattleRewardProgressionUpdateStreamResponse from 'game-data/hooks/definitions/use-battle-reward-progression-update-stream-response';

export default interface BattleRewardProgressionUpdatesWireProps {
  userId: number;
  onEvent: (data: UseBattleRewardProgressionUpdateStreamResponse) => void;
}
