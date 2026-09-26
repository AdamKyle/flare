export default interface DelveRewardCheckpointDefinition {
  label: string;
  requirement: string;
  gold: string;
  special_item: string | null;
  reached: boolean;
}
