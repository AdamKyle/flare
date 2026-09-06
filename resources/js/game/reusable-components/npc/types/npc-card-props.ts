export default interface NpcCardProps {
  npc_id: number;
  name: string;
  type_label?: string | null;
  x?: number | null;
  y?: number | null;
  game_map_name?: string | null;
  on_open_npc?: (npc_id: number) => void;
}
