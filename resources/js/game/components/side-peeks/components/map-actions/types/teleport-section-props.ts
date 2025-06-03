export default interface TeleportSectionProps {
  character_gold: number;
  cost_of_teleport: number;
  on_teleport: () => void;
  can_afford_to_teleport: boolean;
  time_out_value: number;
}
