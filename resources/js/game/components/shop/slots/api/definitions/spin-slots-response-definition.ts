import SpinSlotsRewardDefinition from './spin-slots-reward-definition';

export default interface SpinSlotsResponseDefinition {
  message: string;
  rolls: number[];
  gold: number;
  reward: SpinSlotsRewardDefinition | null;
}
